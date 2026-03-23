/**
 * Logger Module - Structured JSON logging
 */

const levels = { debug: 0, info: 1, warn: 2, error: 3 };
let currentLevel = levels.info;

function initializeLogger(level) {
  const envLevel = process.env.LOG_LEVEL || level;
  currentLevel = levels[envLevel] ?? levels.info;
}

function formatMessage(level, message, context = {}) {
  const logEntry = {
    timestamp: new Date().toISOString(),
    level,
    message,
    service: 'voice-bridge',
    region: process.env.REGION || 'unknown',
    pod_name: process.env.POD_NAME,
    pod_namespace: process.env.POD_NAMESPACE,
    ...context
  };

  return JSON.stringify(logEntry);
}

const logger = {
  debug: (message, context) => {
    if (currentLevel <= levels.debug) {
      console.log(formatMessage('debug', message, context));
    }
  },
  info: (message, context) => {
    if (currentLevel <= levels.info) {
      console.log(formatMessage('info', message, context));
    }
  },
  warn: (message, context) => {
    if (currentLevel <= levels.warn) {
      console.warn(formatMessage('warn', message, context));
    }
  },
  error: (message, context) => {
    if (currentLevel <= levels.error) {
      console.error(formatMessage('error', message, context));
    }
  }
};

export { logger, initializeLogger };
