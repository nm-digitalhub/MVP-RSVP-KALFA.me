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

  const params = new URLSearchParams((req.url || "").split("?")[1] || "");
  const guestId = params.get("guest_id");
  const invitationId = params.get("invitation_id");
  const guestName = decodeURIComponent(params.get("guest_name") || "אורח");

  console.log("[%s] Twilio connected guest_id=%s invitation_id=%s", clientId, guestId, invitationId);

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

  // Google AI Live API (v1beta) expects camelCase in setup; see docs/rsvp-voice-gemini-live-analysis.md
  const setupMessage = {
    setup: {
      model: process.env.GEMINI_LIVE_MODEL || "models/gemini-2.5-flash-native-audio-preview-12-2025",
      generationConfig: {
        responseModalities: ["AUDIO"],
        temperature: 0.2,
      },
      systemInstruction: {
        parts: [
          {
            text: `אתה נציג טלפוני שמקבל אישורי הגעה לאירוע.
שם האורח: ${guestName}
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

  function openGeminiIfNeeded() {
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

      // Send setup first message
      safeSend(geminiWs, JSON.stringify(setupMessage));
    });

    geminiWs.on("message", async (data) => {
      let msg;
      try {
        msg = JSON.parse(data.toString());
      } catch (e) {
        console.error("[%s] Gemini JSON parse error", clientId);
        return;
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

      // Audio output from Gemini -> Twilio
      if (msg.serverContent?.modelTurn?.parts && streamSid) {
        const parts = msg.serverContent.modelTurn.parts;
        for (const part of parts) {
          // Gemini audio typically arrives as inlineData with base64 "data"
          if (part.inlineData?.data) {
            safeSend(
              twilioWs,
              JSON.stringify({
                event: "media",
                streamSid,
                media: { payload: part.inlineData.data }
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
      console.log("[%s] Stream started %s", clientId, streamSid || "(no streamSid)");

      // Open Gemini only when a real Twilio stream starts
      openGeminiIfNeeded();
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

      safeSend(
        geminiWs,
        JSON.stringify({
          realtimeInput: {
            mediaChunks: [
              {
                mimeType: "audio/x-mulaw;rate=8000",
                data: payload
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