# Health Check & Metrics Implementation Guide

## Health Check Endpoint

The `/health` endpoint provides service health status for load balancers and orchestrators.

### Implementation

```javascript
// src/health.js
const http = require('http');

class HealthChecker {
  constructor() {
    this.startTime = Date.now();
    this.connections = new Map(); // Track active connections
  }

  getStats() {
    return {
      status: 'healthy',
      uptime: Math.floor((Date.now() - this.startTime) / 1000),
      version: process.env.APP_VERSION || '1.0.0',
      region: process.env.REGION || 'unknown',
      connections: this.connections.size,
      memory: process.memoryUsage(),
      platform: process.platform,
      nodeVersion: process.version
    };
  }

  isHealthy() {
    // Check Gemini API connectivity
    // Check webhook connectivity
    return true;
  }
}

module.exports = HealthChecker;
```

### Server Integration

```javascript
// src/index.js
const http = require('http');
const healthChecker = new HealthChecker();

// Create HTTP server for health check
const healthServer = http.createServer((req, res) => {
  if (req.url === '/health' && req.method === 'GET') {
    const stats = healthChecker.getStats();
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(stats));
  } else if (req.url === '/metrics' && req.method === 'GET') {
    const metrics = generateMetrics();
    res.writeHead(200, { 'Content-Type': 'text/plain; version=0.0.4' });
    res.end(metrics);
  } else {
    res.writeHead(404);
    res.end('Not Found');
  }
});

healthServer.listen(4001, () => {
  console.log('Health check server listening on port 4001');
});
```

### Health Check Response

```json
{
  "status": "healthy",
  "uptime": 3600,
  "version": "1.0.0",
  "region": "il-central-1",
  "connections": 5,
  "memory": {
    "rss": 45373440,
    "heapTotal": 29360128,
    "heapUsed": 18856976,
    "external": 1042614
  },
  "platform": "linux",
  "nodeVersion": "v20.11.0"
}
```

---

## Metrics Endpoint

The `/metrics` endpoint exposes Prometheus-compatible metrics.

### Metric Types

1. **Gauge** - Current value (can go up or down)
2. **Counter** - Cumulative value (only increases)
3. **Histogram** - Distribution of values

### Implementation

```javascript
// src/metrics.js
class PrometheusMetrics {
  constructor() {
    this.counters = new Map();
    this.gauges = new Map();
    this.histograms = new Map();
  }

  incrementCounter(name, labels = {}) {
    const key = this.getKey(name, labels);
    const current = this.counters.get(key) || 0;
    this.counters.set(key, current + 1);
  }

  setGauge(name, value, labels = {}) {
    const key = this.getKey(name, labels);
    this.gauges.set(key, value);
  }

  observeHistogram(name, value, labels = {}) {
    const key = this.getKey(name, labels);
    if (!this.histograms.has(key)) {
      this.histograms.set(key, []);
    }
    this.histograms.get(key).push(value);
  }

  getKey(name, labels) {
    const labelStr = Object.entries(labels)
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([k, v]) => `${k}="${v}"`)
      .join(',');
    return labelStr ? `${name}{${labelStr}}` : name;
  }

  generate() {
    let output = '';

    // HELP and TYPE for counters
    for (const [name] of this.counters) {
      const baseName = name.split('{')[0];
      output += `# HELP ${baseName} Total count\n`;
      output += `# TYPE ${baseName} counter\n`;
    }
    for (const [key, value] of this.counters) {
      output += `${key} ${value}\n`;
    }

    // HELP and TYPE for gauges
    for (const [name] of this.gauges) {
      const baseName = name.split('{')[0];
      output += `# HELP ${baseName} Current value\n`;
      output += `# TYPE ${baseName} gauge\n`;
    }
    for (const [key, value] of this.gauges) {
      output += `${key} ${value}\n`;
    }

    // HELP and TYPE for histograms
    for (const [name] of this.histograms) {
      const baseName = name.split('{')[0];
      output += `# HELP ${baseName} Distribution of values\n`;
      output += `# TYPE ${baseName} histogram\n`;
    }
    for (const [key, values] of this.histograms) {
      const sorted = values.sort((a, b) => a - b);
      const count = sorted.length;
      const sum = sorted.reduce((a, b) => a + b, 0);

      output += `${key}_count ${count}\n`;
      output += `${key}_sum ${sum}\n`;

      // Calculate quantiles
      for (const q of [0.5, 0.9, 0.95, 0.99]) {
        const index = Math.floor(q * count);
        output += `${key}{quantile="${q}"} ${sorted[index] || 0}\n`;
      }
    }

    return output;
  }
}

module.exports = PrometheusMetrics;
```

### Metrics Exposed

```
# HELP voice_bridge_connections Current number of active WebSocket connections
# TYPE voice_bridge_connections gauge
voice_bridge_connections{region="il-central-1"} 5

# HELP voice_bridge_calls_total Total number of calls processed
# TYPE voice_bridge_calls_total counter
voice_bridge_calls_total{region="il-central-1"} 1234

# HELP voice_bridge_call_duration_seconds Call duration in seconds
# TYPE voice_bridge_call_duration_seconds histogram
voice_bridge_call_duration_seconds_count 1000
voice_bridge_call_duration_seconds_sum 180000
voice_bridge_call_duration_seconds{quantile="0.5"} 150
voice_bridge_call_duration_seconds{quantile="0.9"} 300
voice_bridge_call_duration_seconds{quantile="0.95"} 360
voice_bridge_call_duration_seconds{quantile="0.99"} 600

# HELP voice_bridge_rsvp_saved_total Total number of RSVPs saved
# TYPE voice_bridge_rsvp_saved_total counter
voice_bridge_rsvp_saved_total{intent="yes",region="il-central-1"} 892
voice_bridge_rsvp_saved_total{intent="no",region="il-central-1"} 342

# HELP voice_bridge_errors_total Total number of errors
# TYPE voice_bridge_errors_total counter
voice_bridge_errors_total{type="gemini_connection",region="il-central-1"} 12
voice_bridge_errors_total{type="webhook_timeout",region="il-central-1"} 3
voice_bridge_errors_total{type="audio_conversion",region="il-central-1"} 0

# HELP voice_bridge_latency_seconds Gemini API latency
# TYPE voice_bridge_latency_seconds histogram
voice_bridge_latency_seconds_count 5000
voice_bridge_latency_seconds_sum 250
voice_bridge_latency_seconds{quantile="0.5"} 0.04
voice_bridge_latency_seconds{quantile="0.9"} 0.08
voice_bridge_latency_seconds{quantile="0.95"} 0.1
voice_bridge_latency_seconds{quantile="0.99"} 0.2
```

---

## Structured Logging

### Log Format

All logs should be JSON with consistent fields:

```javascript
// src/logger.js
const pino = require('pino');

const logger = pino({
  level: process.env.LOG_LEVEL || 'info',
  formatters: {
    level: (label) => ({ level: label }),
  },
  timestamp: pino.stdTimeFunctions.isoTime,
  serializers: {
    err: pino.stdSerializers.err,
    req: pino.stdSerializers.req,
    res: pino.stdSerializers.res,
  },
  base: {
    pid: process.pid,
    hostname: process.env.HOSTNAME || 'unknown',
    region: process.env.REGION || 'unknown',
    service: 'voice-bridge',
  },
});

// Add child logger with context
function withContext(context) {
  return logger.child(context);
}

module.exports = { logger, withContext };
```

### Log Examples

```json
// Info log
{
  "level": "info",
  "time": "2025-03-22T10:30:45.123Z",
  "pid": 1234,
  "hostname": "voice-bridge-7d8f9c-k4m2n",
  "region": "il-central-1",
  "service": "voice-bridge",
  "client_id": "abc123",
  "msg": "Call started",
  "call_sid": "CAXxx",
  "guest_name": "John Doe"
}

// Error log
{
  "level": "error",
  "time": "2025-03-22T10:31:00.456Z",
  "pid": 1234,
  "hostname": "voice-bridge-7d8f9c-k4m2n",
  "region": "il-central-1",
  "service": "voice-bridge",
  "client_id": "abc123",
  "msg": "Webhook failed",
  "err": {
    "type": "Error",
    "message": "ECONNREFUSED",
    "stack": "..."
  },
  "url": "https://kalfa.me/api/twilio/rsvp/process"
}
```

---

## Graceful Shutdown

### Signal Handling

```javascript
// src/shutdown.js
class GracefulShutdown {
  constructor(server) {
    this.server = server;
    this.shuttingDown = false;
    this.connections = new Set();

    // Handle shutdown signals
    process.on('SIGTERM', () => this.shutdown('SIGTERM'));
    process.on('SIGINT', () => this.shutdown('SIGINT'));
  }

  trackConnection(ws) {
    this.connections.add(ws);
    ws.on('close', () => this.connections.delete(ws));
  }

  async shutdown(signal) {
    if (this.shuttingDown) return;
    this.shuttingDown = true;

    console.log(`Received ${signal}, starting graceful shutdown...`);

    // Stop accepting new connections
    this.server.close(() => {
      console.log('WebSocket server closed');
    });

    // Wait for active connections to finish (max 30s)
    const deadline = Date.now() + 30000;
    while (this.connections.size > 0 && Date.now() < deadline) {
      console.log(`Waiting for ${this.connections.size} active connections...`);
      await new Promise(resolve => setTimeout(resolve, 1000));
    }

    // Force close remaining connections
    for (const ws of this.connections) {
      ws.close(1001, 'Server shutting down');
    }

    console.log('Shutdown complete');
    process.exit(0);
  }
}

module.exports = GracefulShutdown;
```

---

## Prometheus Alerts

### Alert Rules

```yaml
# prometheus-rules.yaml
groups:
- name: voice_bridge
  interval: 30s
  rules:

  # High error rate alert
  - alert: VoiceBridgeHighErrorRate
    expr: |
      rate(voice_bridge_errors_total[5m]) / rate(voice_bridge_calls_total[5m]) > 0.05
    for: 5m
    labels:
      severity: warning
      component: voice-bridge
    annotations:
      summary: "Voice Bridge error rate above 5%"
      description: "Error rate is {{ $value | humanizePercentage }} for region {{ $labels.region }}"

  # No connections alert (downtime)
  - alert: VoiceBridgeNoConnections
    expr: voice_bridge_connections == 0
    for: 2m
    labels:
      severity: critical
      component: voice-bridge
    annotations:
      summary: "Voice Bridge has no active connections"
      description: "No connections for 2 minutes in region {{ $labels.region }}"

  # High memory usage
  - alert: VoiceBridgeHighMemory
    expr: |
      (voice_bridge_memory_bytes{type="heap_used"} / voice_bridge_memory_bytes{type="heap_total"}) > 0.9
    for: 5m
    labels:
      severity: warning
      component: voice-bridge
    annotations:
      summary: "Voice Bridge memory usage above 90%"
      description: "Memory usage is {{ $value | humanizePercentage }}"

  # High latency alert
  - alert: VoiceBridgeHighLatency
    expr: |
      histogram_quantile(0.95, rate(voice_bridge_latency_seconds_bucket[5m])) > 1
    for: 5m
    labels:
      severity: warning
      component: voice-bridge
    annotations:
      summary: "Voice Bridge latency above 1 second (p95)"
      description: "P95 latency is {{ $value }}s for region {{ $labels.region }}"

  # Webhook failure rate
  - alert: VoiceBridgeWebhookFailures
    expr: |
      rate(voice_bridge_errors_total{type="webhook_*"}[5m]) > 0.1
    for: 5m
    labels:
      severity: critical
      component: voice-bridge
    annotations:
      summary: "Voice Bridge webhook failures increasing"
      description: "Webhook error rate is {{ $value | humanizePercentage }}"
```

---

## Grafana Dashboard

### Panel Queries

```json
{
  "dashboard": {
    "title": "Voice Bridge Metrics",
    "panels": [
      {
        "title": "Active Connections",
        "targets": [
          {
            "expr": "voice_bridge_connections"
          }
        ]
      },
      {
        "title": "Calls Per Minute",
        "targets": [
          {
            "expr": "rate(voice_bridge_calls_total[1m]) * 60"
          }
        ]
      },
      {
        "title": "RSVP Intent Distribution",
        "targets": [
          {
            "expr": "sum by (intent) (rate(voice_bridge_rsvp_saved_total[5m]))"
          }
        ]
      },
      {
        "title": "Error Rate",
        "targets": [
          {
            "expr": "rate(voice_bridge_errors_total[5m]) / rate(voice_bridge_calls_total[5m])"
          }
        ]
      },
      {
        "title": "P95 Latency",
        "targets": [
          {
            "expr": "histogram_quantile(0.95, rate(voice_bridge_latency_seconds_bucket[5m]))"
          }
        ]
      },
      {
        "title": "Memory Usage",
        "targets": [
          {
            "expr": "process_resident_memory_bytes / 1024 / 1024"
          }
        ]
      }
    ]
  }
}
```
