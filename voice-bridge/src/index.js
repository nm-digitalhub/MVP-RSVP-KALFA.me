/**
 * Voice Bridge Microservice - Production Entry Point
 * Twilio Media Stream <-> Gemini Live API relay
 */

import { createServer } from 'http';
import { WebSocketServer } from 'ws';
import { parse } from 'url';
import dotenv from 'dotenv';

// Load environment variables
dotenv.config();

// Import the relay logic
import { createVoiceBridgeHandler } from './relay.js';
import { createHealthHandler } from './health.js';
import { createMetricsHandler } from './metrics.js';
import { logger, initializeLogger } from './logger.js';

// Configuration
const PORT = parseInt(process.env.PORT || '4000', 10);
const LOG_LEVEL = process.env.LOG_LEVEL || 'info';
const REGION = process.env.REGION || 'unknown';

// Validate required environment variables
const requiredEnvVars = ['GEMINI_API_KEY', 'PHP_WEBHOOK'];
const missingVars = requiredEnvVars.filter(varName => !process.env[varName]);
if (missingVars.length > 0) {
  console.error(`[ERROR] Missing required environment variables: ${missingVars.join(', ')}`);
  process.exit(1);
}

// Initialize logger
initializeLogger(LOG_LEVEL);
logger.info('Voice Bridge starting', { port: PORT, region: REGION, version: process.env.npm_package_version || '1.0.0' });

// Create HTTP server for health/metrics endpoints
const server = createServer((req, res) => {
  const parsedUrl = parse(req.url || '', true);
  const pathname = parsedUrl.pathname || '';

  logger.debug('HTTP request', { method: req.method, path: pathname });

  // CORS headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    res.writeHead(200);
    res.end();
    return;
  }

  // Route handlers
  if (pathname === '/health') {
    createHealthHandler(req, res);
  } else if (pathname === '/metrics') {
    createMetricsHandler(req, res);
  } else if (pathname === '/' || pathname === '/readiness') {
    // Root endpoint returns basic info
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({
      service: 'voice-bridge',
      status: 'running',
      version: process.env.npm_package_version || '1.0.0',
      region: REGION,
      websocket: `ws://${req.headers.host || 'localhost:' + PORT}/media`
    }, null, 2));
  } else {
    res.writeHead(404, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Not found' }, null, 2));
  }
});

// Create WebSocket server attached to HTTP server
const wss = new WebSocketServer({
  noServer: true,
  perMessageDeflate: false // Disable compression for audio data
});

// Handle WebSocket upgrade
server.on('upgrade', (req, socket, head) => {
  const { pathname } = parse(req.url || '');

  if (pathname === '/media') {
    wss.handleUpgrade(req, socket, head, (ws) => {
      wss.emit('connection', ws, req);
    });
  } else {
    socket.destroy();
  }
});

// Handle WebSocket connections
wss.on('connection', (ws, req) => {
  createVoiceBridgeHandler(ws, req, logger);
});

// Track active connections
const connections = new Set();
wss.on('connection', (ws) => {
  connections.add(ws);
  logger.debug('WebSocket connected', { activeConnections: connections.size });

  ws.on('close', () => {
    connections.delete(ws);
    logger.debug('WebSocket disconnected', { activeConnections: connections.size });
  });
});

// Graceful shutdown
const shutdown = (signal) => {
  logger.info(`Received ${signal}, starting graceful shutdown...`);

  let pendingClosures = 2;
  let shutdownCompleted = false;

  const forceExitTimer = setTimeout(() => {
    logger.warn('Forcing shutdown after timeout');
    process.exit(1);
  }, 30000);

  const completeShutdown = () => {
    pendingClosures -= 1;

    if (!shutdownCompleted && pendingClosures <= 0) {
      shutdownCompleted = true;
      clearTimeout(forceExitTimer);
      process.exit(0);
    }
  };

  // Stop accepting new connections
  server.close(() => {
    logger.info('HTTP server closed');
    completeShutdown();
  });

  // Close all WebSocket connections
  wss.close(() => {
    logger.info('WebSocket server closed');
    completeShutdown();
  });
};

process.on('SIGTERM', () => shutdown('SIGTERM'));
process.on('SIGINT', () => shutdown('SIGINT'));

// Handle uncaught errors
process.on('uncaughtException', (error) => {
  logger.error('Uncaught exception', { error: error.message, stack: error.stack });
  // Don't exit immediately in production, log and continue
});

process.on('unhandledRejection', (reason, promise) => {
  logger.error('Unhandled rejection', { reason, promise });
});

// Start server
server.listen(PORT, '0.0.0.0', () => {
  logger.info(`Voice Bridge listening on port ${PORT}`, {
    websocket: `ws://0.0.0.0:${PORT}/media`,
    health: `http://0.0.0.0:${PORT}/health`,
    metrics: `http://0.0.0.0:${PORT}/metrics`
  });
});

export { wss, connections };
