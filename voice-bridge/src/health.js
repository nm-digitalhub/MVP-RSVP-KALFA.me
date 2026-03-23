/**
 * Health Check Module
 */

import { logger } from './logger.js';
import { getMetrics } from './metrics.js';

const startTime = Date.now();

export function createHealthHandler(req, res) {
  const metrics = getMetrics();

  const health = {
    status: 'healthy',
    uptime: Math.floor((Date.now() - startTime) / 1000),
    version: process.env.npm_package_version || '1.0.0',
    region: process.env.REGION || 'unknown',
    connections: metrics.activeConnections,
    totalCalls: metrics.totalCalls,
    rsvpsSaved: metrics.rsvpsSaved,
    geminiConnected: metrics.geminiConnected
  };

  res.writeHead(200, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(health, null, 2));

  logger.debug('Health check', health);
}

export function getHealthStatus() {
  const metrics = getMetrics();
  return {
    uptime: Math.floor((Date.now() - startTime) / 1000),
    connections: metrics.activeConnections,
    totalCalls: metrics.totalCalls,
    geminiConnected: metrics.geminiConnected
  };
}
