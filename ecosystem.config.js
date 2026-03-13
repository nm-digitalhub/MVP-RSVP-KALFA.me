/**
 * PM2 ecosystem config for the Node voice/RSVP server (server.js).
 * Do not put secrets here. Set TWILIO_*, GEMINI_API_KEY, PHP_WEBHOOK in the
 * environment or in a .env file and run: node -r dotenv/config node_modules/.bin/pm2 start ecosystem.config.js
 * (or export vars before pm2 start).
 */
import { fileURLToPath } from "url";
import { dirname, join } from "path";

const __dirname = dirname(fileURLToPath(import.meta.url));

const sharedEnv = {
  NODE_ENV: process.env.NODE_ENV || "development",
  BROADCAST_CONNECTION: process.env.BROADCAST_CONNECTION || "pusher",
  TWILIO_ACCOUNT_SID: process.env.TWILIO_ACCOUNT_SID,
  TWILIO_AUTH_TOKEN: process.env.TWILIO_AUTH_TOKEN,
  TWILIO_NUMBER: process.env.TWILIO_NUMBER,
  RSVP_NODE_WS_URL: process.env.RSVP_NODE_WS_URL || "wss://node.kalfa.me/media",
  GEMINI_API_KEY: process.env.GEMINI_API_KEY,
  PHP_WEBHOOK: process.env.PHP_WEBHOOK || "https://kalfa.me/api/twilio/rsvp/process",
};

export default {
  apps: [
    {
      name: "kalfa-reverb",
      script: "/var/www/vhosts/kalfa.me/httpdocs/artisan",
      args: "reverb:start --host=0.0.0.0 --port=6001",
      cwd: "/var/www/vhosts/kalfa.me/httpdocs",
      interpreter: "/opt/plesk/php/8.4/bin/php",
      autorestart: true,
      watch: false,
      max_memory_restart: "200M",
      env: { APP_ENV: "production" },
      env_production: { APP_ENV: "production" },
    },
    {
      name: "kalfa-ai-voice",
      script: join(__dirname, "server.js"),
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
