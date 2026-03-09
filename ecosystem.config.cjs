/**
 * PM2 ecosystem config for the Node voice/RSVP server (server.js).
 * Do not put secrets here. Set TWILIO_*, GEMINI_API_KEY, PHP_WEBHOOK in the
 * environment or in a .env file, then: pm2 start ecosystem.config.cjs --env production
 */
const path = require("path");

const sharedEnv = {
  NODE_ENV: process.env.NODE_ENV || "development",
  BROADCAST_CONNECTION: process.env.BROADCAST_CONNECTION || "pusher",
  TWILIO_ACCOUNT_SID: process.env.TWILIO_ACCOUNT_SID,
  TWILIO_API_KEY: process.env.TWILIO_API_KEY,
  TWILIO_API_SECRET: process.env.TWILIO_API_SECRET,
  TWILIO_NUMBER: process.env.TWILIO_NUMBER,
  RSVP_NODE_WS_URL: process.env.RSVP_NODE_WS_URL || "wss://node.kalfa.me/media",
  GEMINI_API_KEY: process.env.GEMINI_API_KEY,
  PHP_WEBHOOK: process.env.PHP_WEBHOOK || "https://kalfa.me/api/twilio/rsvp/process",
};

module.exports = {
  apps: [
    {
      name: "kalfa-ai-voice",
      script: path.join(__dirname, "server.js"),
      cwd: __dirname,
      instances: 1,
      exec_mode: "fork",
      autorestart: true,
      watch: false,
      max_memory_restart: "300M",

      env: {
        ...sharedEnv,
        NODE_ENV: "development",
      },

      env_production: {
        ...sharedEnv,
        NODE_ENV: "production",
      },
    },
  ],
};
