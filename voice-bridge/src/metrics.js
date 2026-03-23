/**
 * Metrics Module - Prometheus compatible metrics
 */

import client from 'prom-client';
import { logger } from './logger.js';

// Create a Registry
const register = new client.Registry();

// Add default metrics (CPU, memory, etc.)
client.collectDefaultMetrics({ register });

// Custom metrics
const activeConnections = new client.Gauge({
  name: 'voice_bridge_connections',
  help: 'Current number of active WebSocket connections',
  registers: [register]
});

const totalCalls = new client.Counter({
  name: 'voice_bridge_calls_total',
  help: 'Total number of calls processed',
  labelNames: ['region'],
  registers: [register]
});

const rsvpsSaved = new client.Counter({
  name: 'voice_bridge_rsvp_saved_total',
  help: 'Total number of RSVPs saved',
  labelNames: ['intent', 'region'],
  registers: [register]
});

const errors = new client.Counter({
  name: 'voice_bridge_errors_total',
  help: 'Total number of errors',
  labelNames: ['type', 'region'],
  registers: [register]
});

const latency = new client.Histogram({
  name: 'voice_bridge_latency_seconds',
  help: 'Gemini API latency in seconds',
  labelNames: ['operation', 'region'],
  buckets: [0.1, 0.5, 1, 2, 5, 10],
  registers: [register]
});

const geminiConnected = new client.Gauge({
  name: 'voice_bridge_gemini_connected',
  help: 'Whether Gemini API is connected (1) or not (0)',
  registers: [register]
});

// Metrics state
let metricsState = {
  activeConnections: 0,
  totalCalls: 0,
  rsvpsSaved: { yes: 0, no: 0 },
  geminiConnected: 0
};

// Metrics API
export async function createMetricsHandler(req, res) {
  try {
    const metrics = await register.metrics();

    res.setHeader('Content-Type', register.contentType);
    res.writeHead(200);
    res.end(metrics);
  } catch (error) {
    logger.error('Failed to render metrics', {
      error: error instanceof Error ? error.message : String(error),
    });

    res.writeHead(500, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ error: 'Failed to render metrics' }));
  }
}

export function incrementActiveConnections() {
  activeConnections.inc();
  metricsState.activeConnections++;
}

export function decrementActiveConnections() {
  activeConnections.dec();
  metricsState.activeConnections--;
}

export function incrementCalls() {
  const region = process.env.REGION || 'unknown';
  totalCalls.inc({ region });
  metricsState.totalCalls++;
}

export function incrementRsvp(intent) {
  const region = process.env.REGION || 'unknown';
  rsvpsSaved.inc({ intent, region });
  metricsState.rsvpsSaved[intent]++;
}

export function incrementError(type) {
  const region = process.env.REGION || 'unknown';
  errors.inc({ type, region });
}

export function recordLatency(operation, seconds) {
  const region = process.env.REGION || 'unknown';
  latency.observe({ operation, region }, seconds);
}

export function setGeminiConnected(connected) {
  geminiConnected.set(connected ? 1 : 0);
  metricsState.geminiConnected = connected ? 1 : 0;
}

export function getMetrics() {
  return { ...metricsState };
}

export { register };
