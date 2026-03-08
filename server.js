/**
 * Node relay server
 * Twilio <-> Gemini Live API (bidirectional)
 *
 * Requirements:
 *   - Node 18+ (for global fetch)
 *   - package.json contains: { "type": "module" }
 *   - npm i ws dotenv
 *
 * Env:
 *   GEMINI_API_KEY=...
 *   PHP_WEBHOOK=https://kalfa.me/twilio/rsvp-process.php
 *   CALL_LOG_URL=https://kalfa.me/calling-log.php  (optional; for online call log)
 *   CALL_LOG_SECRET=...  (optional; same as config services.twilio.call_log_secret)
 */

import dotenv from "dotenv";
dotenv.config();

import http from "http";
import { WebSocketServer, WebSocket } from "ws";

const server = http.createServer((req, res) => {
  if (req.url === "/health") {
    res.writeHead(200, { "Content-Type": "application/json" });
    res.end(JSON.stringify({ ok: true }));
    return;
  }
  res.writeHead(404);
  res.end("Not Found");
});

const wss = new WebSocketServer({ server, path: "/media" });

const GEMINI_API_KEY = process.env.GEMINI_API_KEY;
if (!GEMINI_API_KEY) console.warn("[WARN] GEMINI_API_KEY is not set");
else console.log("Gemini API key loaded");

// Gemini Live WebSocket endpoint (BidiGenerateContent)
const GEMINI_WS =
  `wss://generativelanguage.googleapis.com/ws/google.ai.generativelanguage.v1beta.GenerativeService.BidiGenerateContent?key=${GEMINI_API_KEY}`;



const PHP_WEBHOOK = process.env.PHP_WEBHOOK || "https://kalfa.me/twilio/rsvp-process.php";
const CALL_LOG_URL = process.env.CALL_LOG_URL || "";
const CALL_LOG_SECRET = process.env.CALL_LOG_SECRET || "";

function sendCallLog(callSid, role, text) {
  if (!callSid || !CALL_LOG_URL || !text?.trim()) return;
  const params = new URLSearchParams({ call_sid: callSid, role, text });
  if (CALL_LOG_SECRET) params.set("key", CALL_LOG_SECRET);
  fetch(CALL_LOG_URL, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: params.toString(),
  }).catch(() => {});
}

/**
 * Twilio sends mulaw 8kHz; Gemini Live expects PCM 16-bit 16kHz.
 * Converts base64 mulaw 8k to base64 PCM 16k for Gemini realtimeInput.
 */
function mulaw8kBase64ToPcm16kBase64(base64) {
  const buf = Buffer.from(base64, "base64");
  const mulaw = new Uint8Array(buf.buffer, buf.byteOffset, buf.length);
  const BIAS = 0x84;
  const mulawToPcm = (mu) => {
    mu = ~mu;
    const sign = mu & 0x80;
    const exponent = (mu >> 4) & 0x07;
    const mantissa = mu & 0x0f;
    let sample = ((mantissa << 3) + BIAS) << exponent;
    if (sign) sample = -sample;
    return sample;
  };
  const pcm8k = new Int16Array(mulaw.length);
  for (let i = 0; i < mulaw.length; i++) pcm8k[i] = mulawToPcm(mulaw[i]);
  const pcm16k = new Int16Array(pcm8k.length * 2);
  for (let i = 0; i < pcm8k.length; i++) {
    pcm16k[i * 2] = pcm8k[i];
    pcm16k[i * 2 + 1] = pcm8k[i];
  }
  return Buffer.from(pcm16k.buffer).toString("base64");
}

/**
 * Gemini Live outputs PCM 16-bit 24kHz; Twilio expects mulaw 8kHz.
 * Converts base64 PCM24k to base64 mulaw 8k for Twilio media events.
 */
function pcm24kBase64ToMulaw8kBase64(base64) {
  const buf = Buffer.from(base64, "base64");
  const pcm24k = new Int16Array(buf.buffer, buf.byteOffset, buf.length / 2);
  const n8k = Math.floor(pcm24k.length / 3);
  const mulaw = new Uint8Array(n8k);
  const BIAS = 0x84;
  const CLIP = 32635;
  const pcmToMuLaw = (sample) => {
    let s = sample;
    const sign = (s >> 8) & 0x80;
    if (sign) s = -s;
    if (s > CLIP) s = CLIP;
    s += BIAS;
    let exponent = 7;
    for (let expMask = 0x4000; (s & expMask) === 0 && exponent > 0; expMask >>= 1) exponent--;
    const mantissa = (s >> (exponent + 3)) & 0x0f;
    return (~(sign | (exponent << 4) | mantissa)) & 0xff;
  };
  for (let i = 0; i < n8k; i++) {
    const a = pcm24k[i * 3];
    const b = pcm24k[i * 3 + 1] ?? a;
    const c = pcm24k[i * 3 + 2] ?? b;
    mulaw[i] = pcmToMuLaw(Math.round((a + b + c) / 3));
  }
  return Buffer.from(mulaw).toString("base64");
}

function safeSend(ws, payload) {
  if (!ws) return false;
  if (ws.readyState !== WebSocket.OPEN) return false;
  ws.send(payload);
  return true;
}

function safeClose(ws, code = 1000, reason = "") {
  try {
    if (ws && (ws.readyState === WebSocket.OPEN || ws.readyState === WebSocket.CONNECTING)) {
      ws.close(code, reason);
    }
  } catch (_) {}
}

wss.on("connection", (twilioWs, req) => {
  const clientId = `${Date.now()}-${Math.random().toString(36).slice(2, 7)}`;

  // Twilio passes custom params in the "start" message (not in URL); fallback to URL for older clients
  const urlParams = new URLSearchParams((req.url || "").split("?")[1] || "");
  let guestId = urlParams.get("guest_id");
  let invitationId = urlParams.get("invitation_id");
  let guestName = decodeURIComponent(urlParams.get("guest_name") || "אורח");
  let eventName = decodeURIComponent(urlParams.get("event_name") || "");
  let eventDate = decodeURIComponent(urlParams.get("event_date") || "");
  let eventVenue = decodeURIComponent(urlParams.get("event_venue") || "");
  let callSid = null;

  console.log("[%s] Twilio connected (params from start message or URL)", clientId);

  let streamSid = null;

  // Gemini state
  let geminiWs = null;
  let geminiSetupComplete = false;
  let geminiOpening = false;

  // Buffer audio until Gemini setupComplete
  const pendingAudioChunks = []; // base64 payloads from Twilio

  const heartbeat = setInterval(() => {
    try {
      if (twilioWs.readyState === WebSocket.OPEN) twilioWs.ping();
    } catch (_) {}
  }, 20000);

  function buildSetupMessage(guestNameForSetup, eventNameForSetup, eventDateForSetup, eventVenueForSetup) {
    const eventLine =
      eventNameForSetup || eventDateForSetup || eventVenueForSetup
        ? [
            "בתחילת השיחה הצג לאורח לאיזה אירוע זה אישור: שם האירוע, תאריך ומקום (אם יש).",
            eventNameForSetup ? `שם האירוע: ${eventNameForSetup}` : "",
            eventDateForSetup ? `תאריך: ${eventDateForSetup}` : "",
            eventVenueForSetup ? `מקום: ${eventVenueForSetup}` : "",
          ]
            .filter(Boolean)
            .join("\n") + "\n\n"
        : "";
    return {
      setup: {
        model: process.env.GEMINI_LIVE_MODEL || "models/gemini-2.5-flash-native-audio-preview-12-2025",
        generationConfig: {
          responseModalities: ["AUDIO"],
          temperature: 0.15,
        },
        outputAudioTranscription: {},
        inputAudioTranscription: {},
        systemInstruction: {
          parts: [
            {
              text: `אתה נציג טלפוני שמקבל אישורי הגעה לאירוע.
הגייה: דבר בעברית תקנית, בהגייה ברורה ומדויקת. הבהר כל מילה, בקצב בינוני (לא מהיר). הימנע מלדבר מהר או למלמל.
${eventLine}שם האורח: ${guestNameForSetup}
שאל אם הוא מגיע וכמה אנשים.
אם הוא מגיע: intent=yes
אם לא: intent=no
לאחר קבלת תשובה ברורה (כן/לא + כמות), קרא לפונקציה save_rsvp ואז תגיד תודה ולהתראות.`
            }
          ]
        },
        tools: [
          {
            functionDeclarations: [
              {
                name: "save_rsvp",
                description: "Save RSVP result",
                parameters: {
                  type: "OBJECT",
                  properties: {
                    intent: { type: "STRING", enum: ["yes", "no"] },
                    number_of_guests: { type: "INTEGER" }
                  },
                  required: ["intent", "number_of_guests"]
                }
              }
            ]
          }
        ]
      }
    };
  }

  function openGeminiIfNeeded(setupMessageToSend) {
    if (geminiWs && (geminiWs.readyState === WebSocket.OPEN || geminiWs.readyState === WebSocket.CONNECTING)) {
      return;
    }
    if (geminiOpening) return;
    geminiOpening = true;

    geminiSetupComplete = false;

    geminiWs = new WebSocket(GEMINI_WS, { handshakeTimeout: 10000 });

    geminiWs.on("open", () => {
      console.log("[%s] Gemini connected", clientId);
      geminiOpening = false;

      const msg = setupMessageToSend || buildSetupMessage(guestName);
      safeSend(geminiWs, JSON.stringify(msg));
    });

    geminiWs.on("message", async (data) => {
      let msg;
      try {
        msg = JSON.parse(data.toString());
      } catch (e) {
        console.error("[%s] Gemini JSON parse error", clientId);
        return;
      }

      // Transcript: send to call log for online display
      if (msg.serverContent?.outputTranscription?.text) {
        sendCallLog(callSid, "bot", msg.serverContent.outputTranscription.text);
      }
      if (msg.serverContent?.inputTranscription?.text) {
        sendCallLog(callSid, "user", msg.serverContent.inputTranscription.text);
      }

      // IMPORTANT: wait for setupComplete before sending additional messages
      if (msg.setupComplete) {
        geminiSetupComplete = true;
        console.log("[%s] Gemini setupComplete", clientId);

        // Make Gemini speak first (text turn). This is optional but usually desired for IVR.
        safeSend(
          geminiWs,
          JSON.stringify({
            clientContent: {
              turns: [
                {
                  role: "user",
                  parts: [{ text: "תפתח את השיחה בברכה קצרה בעברית ותשאל אם מגיעים וכמה אנשים." }]
                }
              ],
              turnComplete: true
            }
          })
        );

        // Flush buffered audio if any (skip to avoid 1007 until clientContent is confirmed working)
        const toFlush = pendingAudioChunks.length;
        if (toFlush) {
          console.log("[%s] Skipping flush of %d buffered audio chunks after first clientContent", clientId, toFlush);
        }
        pendingAudioChunks.length = 0;

        return;
      }

      // Tool call (function calling)
      if (msg.toolCall) {
        const tool = msg.toolCall.functionCalls?.[0];
        if (tool?.name === "save_rsvp") {
          const args = tool.args || {};
          const intent = args.intent;
          const numberOfGuests = Number.isFinite(args.number_of_guests) ? args.number_of_guests : parseInt(args.number_of_guests, 10);

          try {
            await fetch(PHP_WEBHOOK, {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                guest_id: guestId,
                invitation_id: invitationId,
                intent,
                number_of_guests: Number.isFinite(numberOfGuests) ? numberOfGuests : 0
              })
            });
            console.log("[%s] PHP webhook saved intent=%s guests=%s", clientId, intent, numberOfGuests);
            const summary = intent === "yes" ? `נשמר: מגיעים, ${Number.isFinite(numberOfGuests) ? numberOfGuests : 0} אנשים` : "נשמר: לא מגיעים";
            sendCallLog(callSid, "bot", summary);
          } catch (err) {
            console.error("[%s] PHP webhook failed", clientId, err?.message || err);
          }

          // Respond to Gemini tool call
          safeSend(
            geminiWs,
            JSON.stringify({
              toolResponse: {
                functionResponses: [
                  {
                    id: tool.id || "save_rsvp",
                    name: "save_rsvp",
                    response: { result: "ok" }
                  }
                ]
              }
            })
          );
        }
      }

      // Audio output from Gemini (PCM 24kHz) -> convert to mulaw 8kHz for Twilio
      if (msg.serverContent?.modelTurn?.parts && streamSid) {
        const parts = msg.serverContent.modelTurn.parts;
        for (const part of parts) {
          if (part.inlineData?.data) {
            const twilioPayload = pcm24kBase64ToMulaw8kBase64(part.inlineData.data);
            safeSend(
              twilioWs,
              JSON.stringify({
                event: "media",
                streamSid,
                media: { payload: twilioPayload }
              })
            );
          }
        }
      }
    });

    geminiWs.on("error", (err) => {
      console.error("[%s] Gemini error: %s", clientId, err?.message || err);
    });

    geminiWs.on("close", (code, reason) => {
      console.log("[%s] Gemini closed code=%s reason=%s", clientId, code, reason?.toString?.() || "");
      geminiOpening = false;
      geminiSetupComplete = false;

      // Tell Twilio to stop streaming (optional)
      safeSend(twilioWs, JSON.stringify({ event: "stop" }));
    });
  }

  twilioWs.on("message", (raw) => {
    let msg;
    try {
      msg = JSON.parse(raw.toString());
    } catch (_) {
      // wscat / probes sometimes send non-JSON; ignore silently
      return;
    }

    if (msg.event === "start") {
      streamSid = msg.start?.streamSid || msg.streamSid || null;
      callSid = msg.start?.callSid || msg.start?.call_sid || null;
      const custom = msg.start?.customParameters || {};
      if (Object.keys(custom).length) {
        guestId = custom.guest_id ?? guestId;
        invitationId = custom.invitation_id ?? invitationId;
        guestName = custom.guest_name ? String(custom.guest_name) : guestName;
        eventName = custom.event_name ? String(custom.event_name) : eventName;
        eventDate = custom.event_date ? String(custom.event_date) : eventDate;
        eventVenue = custom.event_venue ? String(custom.event_venue) : eventVenue;
        console.log("[%s] Stream started guest_id=%s invitation_id=%s guest_name=%s event=%s callSid=%s", clientId, guestId, invitationId, guestName, eventName || "(none)", callSid || "(none)");
      } else {
        console.log("[%s] Stream started %s (no customParameters)", clientId, streamSid || "(no streamSid)");
      }

      openGeminiIfNeeded(buildSetupMessage(guestName, eventName, eventDate, eventVenue));
      return;
    }

    if (msg.event === "media") {
      const payload = msg.media?.payload;
      if (!payload) return;

      // If Gemini isn't ready yet, buffer a small amount
      if (!geminiWs || geminiWs.readyState !== WebSocket.OPEN || !geminiSetupComplete) {
        if (pendingAudioChunks.length < 50) pendingAudioChunks.push(payload);
        return;
      }

      // Backpressure safety
      if (geminiWs.bufferedAmount > 1e6) return;

      const pcm16kBase64 = mulaw8kBase64ToPcm16kBase64(payload);
      safeSend(
        geminiWs,
        JSON.stringify({
          realtimeInput: {
            mediaChunks: [
              {
                mimeType: "audio/pcm;rate=16000",
                data: pcm16kBase64
              }
            ]
          }
        })
      );
      return;
    }

    if (msg.event === "stop") {
      console.log("[%s] Twilio stop", clientId);
      safeClose(geminiWs, 1000, "Twilio stop");
      return;
    }
  });

  twilioWs.on("close", (code, reason) => {
    console.log("[%s] Twilio closed code=%s reason=%s", clientId, code, reason?.toString?.() || "");
    clearInterval(heartbeat);
    safeClose(geminiWs, 1000, "client closed");
  });

  twilioWs.on("error", (err) => {
    console.error("[%s] Twilio error: %s", clientId, err?.message || err);
  });
});

server.listen(4000, "0.0.0.0", () => {
  console.log("Node WebSocket server running on port 4000 (WS path /media)");
});