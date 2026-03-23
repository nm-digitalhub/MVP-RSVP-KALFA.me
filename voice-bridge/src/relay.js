/**
 * Relay Module - Twilio <-> Gemini bidirectional relay
 */

import WebSocket from 'ws';
import { parse } from 'url';
import { logger } from './logger.js';
import * as metrics from './metrics.js';

// Configuration from environment
const GEMINI_API_KEY = process.env.GEMINI_API_KEY;
const GEMINI_MODEL = process.env.GEMINI_MODEL || 'models/gemini-2.0-flash-exp';
const GEMINI_WS = `wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1alpha.GenerativeService.BidiGenerateContent?key=${GEMINI_API_KEY}`;
const PHP_WEBHOOK = process.env.PHP_WEBHOOK;
const CALL_LOG_URL = process.env.CALL_LOG_URL;
const CALL_LOG_SECRET = process.env.CALL_LOG_SECRET;
const HEALTH_CHECK_INTERVAL = parseInt(process.env.HEALTH_CHECK_INTERVAL || '30000', 10);

export function createVoiceBridgeHandler(twilioWs, req, logger) {
  const urlParams = new URLSearchParams(req.url.split('?')[1] || '');
  const clientId = Math.random().toString(36).substring(7);

  // Extract guest context from URL parameters
  let guestId = urlParams.get('guest_id');
  let invitationId = urlParams.get('invitation_id');
  let guestName = decodeURIComponent(urlParams.get('guest_name') || 'אורח');
  let eventName = decodeURIComponent(urlParams.get('event_name') || '');
  let eventDate = decodeURIComponent(urlParams.get('event_date') || '');
  let eventVenue = decodeURIComponent(urlParams.get('event_venue') || '');
  let eventAddress = decodeURIComponent(urlParams.get('event_address') || '');
  let eventDescription = decodeURIComponent(urlParams.get('event_description') || '');
  let eventProgram = decodeURIComponent(urlParams.get('event_program') || '');
  let eventCustom = decodeURIComponent(urlParams.get('event_custom') || '[]');
  let guestSeating = decodeURIComponent(urlParams.get('guest_seating') || 'טרם נקבע');
  let callSid = null;

  metrics.incrementActiveConnections();
  logger.info('Twilio connected', { clientId, guestName, eventName });

  let streamSid = null;
  let geminiWs = null;
  let geminiSetupComplete = false;
  let geminiOpening = false;
  const pendingAudioChunks = [];

  // Heartbeat to keep connection alive
  const heartbeat = setInterval(() => {
    try {
      if (twilioWs.readyState === WebSocket.OPEN) {
        twilioWs.ping();
      }
    } catch (err) {
      logger.warn('Heartbeat failed', { clientId, error: err.message });
    }
  }, HEALTH_CHECK_INTERVAL);

  // Build Gemini setup message with guest context
  function buildSetupMessage() {
    let customQuestions = [];
    try {
      customQuestions = JSON.parse(eventCustom || '[]');
    } catch (e) {
      logger.warn('Failed to parse custom questions', { clientId, error: e.message });
    }

    const context = [
      `שם האורח: ${guestName}`,
      `אירוע: ${eventName}`,
      `תאריך: ${eventDate}`,
      `מקום: ${eventVenue}`,
      eventAddress ? `כתובת: ${eventAddress}` : '',
      `הושבה: ${guestSeating}`,
      eventDescription ? `מידע נוסף: ${eventDescription}` : '',
      eventProgram ? `לו"ז: ${eventProgram}` : '',
      customQuestions.length > 0 ? 'שאלות מארח: ' + customQuestions.map(q => q.label).join(', ') : ''
    ].filter(Boolean).join('\n');

    return {
      setup: {
        model: GEMINI_MODEL,
        generationConfig: {
          responseModalities: ['AUDIO'],
          temperature: 0.7,
          speechConfig: {
            voiceConfig: {
              prebuiltVoiceConfig: {
                voiceName: 'Aoede'
              }
            }
          }
        },
        inputAudioTranscriptionConfig: { enabled: true },
        outputAudioTranscriptionConfig: { enabled: true },
        systemInstruction: {
          parts: [{
            text: `אתה נציג אירוח אישי במצב YOLO - חכם, מהיר ואנושי.
תפקידך: לנהל שיחת אירוח מושלמת בעברית טבעית (ישראלית "צברית").

מידע על האירוע:
${context}

הנחיות YOLO:
1. פתח בברכה חמה (למשל: "שלום ${guestName}, אני נציג האירוח של ${eventName}").
2. ענה על כל שאלה (מיקום, הושבה, לו"ז) בביטחון ובסבלנות.
3. רק כשהאורח מוכן, בצע save_rsvp עם הכוונת (yes/no) וכמות האנשים.
4. אם יש שאלות מארח, שאל אותן בטבעיות ותעד את התשובות ב-notes.
5. כשהשיחה מסתיימת, קרא ל-end_call לניתוק הקו.

דגש הגייה: דבר ברור, השתמש במילות קישור טבעיות, ואל תישמע כמו רובוט. אם האורח מתפרץ, עצור והקשב.`
          }]
        },
        tools: [{
          functionDeclarations: [
            {
              name: 'save_rsvp',
              description: 'Save guest response',
              parameters: {
                type: 'OBJECT',
                properties: {
                  intent: { type: 'STRING', enum: ['yes', 'no'] },
                  number_of_guests: { type: 'INTEGER' },
                  notes: { type: 'STRING' }
                },
                required: ['intent', 'number_of_guests']
              }
            },
            {
              name: 'end_call',
              description: 'Hangs up the phone',
              parameters: { type: 'OBJECT', properties: {} }
            }
          ]
        }]
      }
    };
  }

  // Open Gemini WebSocket connection
  function openGemini() {
    if (geminiOpening || (geminiWs && geminiWs.readyState <= 1)) return;
    geminiOpening = true;
    const startTime = Date.now();

    geminiWs = new WebSocket(GEMINI_WS);

    geminiWs.on('open', () => {
      logger.info('Gemini connected', { clientId });
      metrics.setGeminiConnected(true);
      metrics.recordLatency('gemini_connect', (Date.now() - startTime) / 1000);
      geminiOpening = false;
      safeSend(geminiWs, JSON.stringify(buildSetupMessage()));
    });

    geminiWs.on('message', async (data) => {
      const msg = JSON.parse(data);

      if (msg.setupComplete) {
        geminiSetupComplete = true;
        logger.info('Gemini setup complete', { clientId });
        // Initial greeting trigger
        safeSend(geminiWs, JSON.stringify({
          clientContent: {
            turns: [{ role: 'user', parts: [{ text: 'האורח בקו, התחל בברכה חמה.' }] }],
            turnComplete: true
          }
        }));
        // Flush buffered audio
        while (pendingAudioChunks.length > 0) {
          sendAudioToGemini(pendingAudioChunks.shift());
        }
      }

      if (msg.serverContent?.modelDraft?.audio) {
        const base64Audio = Buffer.from(msg.serverContent.modelDraft.audio, 'base64').toString('base64');
        safeSend(twilioWs, JSON.stringify({ event: 'media', streamSid, media: { payload: base64Audio } }));
      }

      if (msg.serverContent?.interrupted) {
        safeSend(twilioWs, JSON.stringify({ event: 'clear', streamSid }));
      }

      if (msg.serverContent?.inputTranscription?.text) {
        logger.info('User transcription', { clientId, text: msg.serverContent.inputTranscription.text });
        sendCallLog(callSid, 'user', msg.serverContent.inputTranscription.text);
      }

      if (msg.serverContent?.outputTranscription?.text) {
        logger.info('Bot transcription', { clientId, text: msg.serverContent.outputTranscription.text });
        sendCallLog(callSid, 'bot', msg.serverContent.outputTranscription.text);
      }

      if (msg.toolCall) {
        for (const call of msg.toolCall.functionCalls) {
          if (call.name === 'save_rsvp') {
            const { intent, number_of_guests, notes } = call.args;
            try {
              const response = await fetch(PHP_WEBHOOK, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ guest_id: guestId, invitation_id: invitationId, intent, number_of_guests, notes })
              });

              if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
              }

              logger.info('RSVP saved', { clientId, intent, number_of_guests });
              metrics.incrementRsvp(intent);
              sendCallLog(callSid, 'bot', `נשמר: ${intent === 'yes' ? 'מגיע' : 'לא מגיע'}, ${number_of_guests} אורחים.`);
              safeSend(geminiWs, JSON.stringify({
                toolResponse: { functionResponses: [{ id: call.id, name: 'save_rsvp', response: { result: 'ok' } }] }
              }));
            } catch (err) {
              logger.error('Failed to save RSVP', { clientId, error: err.message, intent, number_of_guests });
              metrics.incrementError('rsvp_save');
            }
          }
          if (call.name === 'end_call') {
            logger.info('Ending call', { clientId });
            setTimeout(() => {
              if (twilioWs.readyState === WebSocket.OPEN) {
                twilioWs.close();
              }
            }, 2000);
          }
        }
      }
    });

    geminiWs.on('error', (error) => {
      logger.error('Gemini WebSocket error', { clientId, error: error.message });
      metrics.incrementError('gemini_websocket');
      metrics.setGeminiConnected(false);
    });

    geminiWs.on('close', () => {
      logger.info('Gemini closed', { clientId });
      geminiSetupComplete = false;
      metrics.setGeminiConnected(false);
    });
  }

  // Send audio chunk to Gemini
  function sendAudioToGemini(payload) {
    if (!geminiSetupComplete) return;
    const pcm = mulaw8kBase64ToPcm16kBase64(payload);
    safeSend(geminiWs, JSON.stringify({
      realtimeInput: { mediaChunks: [{ mimeType: 'audio/l16;rate=16000', data: pcm }] }
    }));
  }

  // Handle Twilio messages
  twilioWs.on('message', (data) => {
    try {
      const msg = JSON.parse(data);

      if (msg.event === 'start') {
        streamSid = msg.start.streamSid;
        callSid = msg.start.callSid;
        const params = msg.start.customParameters || {};

        // Override URL params with custom parameters if provided
        guestId = params.guest_id || guestId;
        invitationId = params.invitation_id || invitationId;
        guestName = params.guest_name || guestName;
        eventName = params.event_name || eventName;
        eventDate = params.event_date || eventDate;
        eventVenue = params.event_venue || eventVenue;
        eventAddress = params.event_address || eventAddress;
        eventDescription = params.event_description || eventDescription;
        eventProgram = params.event_program || eventProgram;
        eventCustom = params.event_custom || eventCustom;
        guestSeating = params.guest_seating || guestSeating;

        metrics.incrementCalls();
        logger.info('Call started', { clientId, callSid, guestName, eventName });
        openGemini();
      }

      if (msg.event === 'media') {
        if (!geminiSetupComplete) {
          pendingAudioChunks.push(msg.media.payload);
        } else {
          sendAudioToGemini(msg.media.payload);
        }
      }

      if (msg.event === 'stop') {
        logger.info('Call stopped', { clientId });
        if (geminiWs) geminiWs.close();
        clearInterval(heartbeat);
        metrics.decrementActiveConnections();
      }
    } catch (err) {
      logger.error('Failed to process Twilio message', { clientId, error: err.message });
      metrics.incrementError('twilio_message');
    }
  });

  // Handle Twilio close
  twilioWs.on('close', () => {
    logger.info('Twilio disconnected', { clientId });
    if (geminiWs) geminiWs.close();
    clearInterval(heartbeat);
    metrics.decrementActiveConnections();
  });

  twilioWs.on('error', (error) => {
    logger.error('Twilio WebSocket error', { clientId, error: error.message });
    metrics.incrementError('twilio_websocket');
  });
}

// Utility: Send WebSocket message safely
function safeSend(ws, data) {
  if (ws && ws.readyState === WebSocket.OPEN) {
    ws.send(data);
  }
}

// Utility: Send call log to Laravel
async function sendCallLog(callSid, role, text) {
  if (!callSid || !text) return;

  try {
    await fetch(CALL_LOG_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Call-Log-Secret': CALL_LOG_SECRET
      },
      body: JSON.stringify({ call_sid: callSid, role, text, secret: CALL_LOG_SECRET })
    });
  } catch (err) {
    logger.warn('Failed to send call log', { callSid, error: err.message });
  }
}

// Utility: Convert μ-law 8kHz to PCM 16kHz
function mulaw8kBase64ToPcm16kBase64(base64) {
  const buffer = Buffer.from(base64, 'base64');
  const pcm = Buffer.alloc(buffer.length * 4); // 2 bytes per sample * 2x upsampling

  for (let i = 0; i < buffer.length; i++) {
    const ulaw = buffer[i];
    let s = ~(ulaw);
    let exp = (s >> 4) & 0x07;
    let mant = s & 0x0F;
    let val = (mant << 3) + 0x84;
    val <<= exp;
    val = (s & 0x80) ? (0x84 - val) : (val - 0x84);

    // Double sample for 8k -> 16k
    pcm.writeInt16LE(val, i * 4);
    pcm.writeInt16LE(val, i * 4 + 2);
  }

  return pcm.toString('base64');
}
