import { fileURLToPath } from "url";
import { dirname, join } from "path";

const __dirname = dirname(fileURLToPath(import.meta.url));

export default {
  apps: [
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
        NODE_ENV: "development",
        BROADCAST_CONNECTION: "pusher",
        TWILIO_ACCOUNT_SID: "ACd110e72980997ed07a617c987480e396",
        TWILIO_AUTH_TOKEN: "5cb0cf09958860e8252160c7fd63b993",
        TWILIO_NUMBER: "+972722577553",
        RSVP_NODE_WS_URL: "wss://node.kalfa.me/media",
        GEMINI_API_KEY: "AIzaSyDogGQZXK0v_zBtmMJZ3s4qoPBs9HfZdH4",
        PHP_WEBHOOK: "https://kalfa.me/twilio/rsvp-process.php"
      },

      env_production: {
        NODE_ENV: "production",
        BROADCAST_CONNECTION: "pusher",
        TWILIO_ACCOUNT_SID: "ACd110e72980997ed07a617c987480e396",
        TWILIO_AUTH_TOKEN: "5cb0cf09958860e8252160c7fd63b993",
        TWILIO_NUMBER: "+972722577553",
        RSVP_NODE_WS_URL: "wss://node.kalfa.me/media",
        GEMINI_API_KEY: "AIzaSyDogGQZXK0v_zBtmMJZ3s4qoPBs9HfZdH4",
        PHP_WEBHOOK: "https://kalfa.me/twilio/rsvp-process.php"
      }
    }
  ]
};
