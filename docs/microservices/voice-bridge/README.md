# Voice Bridge Microservice

Twilio Media Stream → Gemini Live API relay service for RSVP voice calls.

## Overview

This service acts as a bidirectional relay between:
- **Twilio Media Stream** (WebSocket, μ-law 8kHz audio)
- **Google Gemini Live API** (WebSocket, PCM 16kHz audio)

When a guest RSVP response is detected, it posts to the Laravel webhook endpoint.

## Architecture

```
┌─────────────┐         WebSocket          ┌──────────────┐
│   Twilio    │ ◄──────────────────────────► │              │
│  MediaStream│      (μ-law 8kHz audio)     │  Voice       │
│             │ ◄──────────────────────────► │  Bridge      │
└─────────────┘         Relay              │              │
                                            └──────┬───────┘
                                                   │
                                         WebSocket (Gemini Live)
                                                   │
                                            ┌──────▼───────┐
                                            │   Google     │
                                            │   Gemini     │
                                            │   Live API   │
                                            └──────────────┘

                                                   │
                                            HTTP POST (RSVP data)
                                                   │
                                            ┌──────▼───────┐
                                            │   Laravel    │
                                            │   Webhook    │
                                            │  /api/twilio │
                                            └──────────────┘
```

## Features

- ✅ Bidirectional audio relay (Twilio ↔ Gemini)
- ✅ Hebrew language support with natural TTS
- ✅ Guest context injection (event details, seating, etc.)
- ✅ RSVP intent detection with `save_rsvp` tool
- ✅ Call logging to Laravel
- ✅ Graceful shutdown for zero-downtime deploys
- ✅ Health check endpoint
- ✅ Prometheus metrics
- ✅ Structured JSON logging
- ✅ Correlation IDs for tracing

## Quick Start

### Docker

```bash
docker build -t voice-bridge:latest .
docker run -p 4000:4000 \
  -e GEMINI_API_KEY=$GEMINI_API_KEY \
  -e PHP_WEBHOOK=https://kalfa.me/api/twilio/rsvp/process \
  voice-bridge:latest
```

### Kubernetes

```bash
kubectl apply -f k8s/
```

## Configuration

### Environment Variables

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `PORT` | No | `4000` | WebSocket server port |
| `GEMINI_API_KEY` | Yes | - | Google Gemini API key |
| `GEMINI_MODEL` | No | `models/gemini-2.0-flash-exp` | Gemini model to use |
| `PHP_WEBHOOK` | Yes | - | Laravel webhook URL for RSVP processing |
| `CALL_LOG_URL` | No | - | Laravel call log URL |
| `CALL_LOG_SECRET` | No | - | Secret for call log authentication |
| `LOG_LEVEL` | No | `info` | Logging level (debug, info, warn, error) |
| `HEALTH_CHECK_INTERVAL` | No | `30000` | Heartbeat interval to Twilio (ms) |

### Guest Context (URL Parameters)

When Twilio connects, include these in the WebSocket URL:

| Parameter | Type | Description |
|-----------|------|-------------|
| `guest_id` | string | Guest UUID |
| `invitation_id` | string | Invitation UUID |
| `guest_name` | string | Guest name (URL-encoded) |
| `event_name` | string | Event name (URL-encoded) |
| `event_date` | string | Event date (URL-encoded) |
| `event_venue` | string | Event venue (URL-encoded) |
| `event_address` | string | Event address (URL-encoded) |
| `event_description` | string | Additional event info (URL-encoded) |
| `event_program` | string | Event program/agenda (URL-encoded) |
| `event_custom` | string | JSON array of custom questions (URL-encoded) |
| `guest_seating` | string | Guest seating assignment (URL-encoded) |

Example WebSocket URL:
```
wss://voice-bridge.kalfa.me/media?guest_id=abc123&guest_name=John%20Doe&event_name=Wedding&event_date=2025-04-15
```

## API Endpoints

### WebSocket Endpoint

**URL:** `ws://localhost:4000/media` or `wss://voice-bridge.kalfa.me/media`

**Protocol:** WebSocket

**Messages from Twilio:**
```json
{
  "event": "start",
  "start": {
    "streamSid": "MTxxx",
    "callSid": "CAXxx",
    "customParameters": {
      "guest_id": "abc123",
      "guest_name": "John Doe"
    }
  }
}
```

```json
{
  "event": "media",
  "media": {
    "payload": "<base64 μ-law audio>"
  }
}
```

```json
{
  "event": "stop"
}
```

**Messages to Twilio:**
```json
{
  "event": "media",
  "streamSid": "MTxxx",
  "media": {
    "payload": "<base64 μ-law audio>"
  }
}
```

```json
{
  "event": "clear",
  "streamSid": "MTxxx"
}
```

### Health Check

**URL:** `GET /health`

**Response:**
```json
{
  "status": "healthy",
  "uptime": 3600,
  "version": "1.0.0",
  "connections": 5
}
```

### Metrics (Prometheus)

**URL:** `GET /metrics`

**Response:** Prometheus text format

```
# HELP voice_bridge_connections Current number of active WebSocket connections
# TYPE voice_bridge_connections gauge
voice_bridge_connections 5

# HELP voice_bridge_calls_total Total number of calls processed
# TYPE voice_bridge_calls_total counter
voice_bridge_calls_total{region="il-central-1"} 1234

# HELP voice_bridge_rsvp_saved_total Total number of RSVPs saved
# TYPE voice_bridge_rsvp_saved_total counter
voice_bridge_rsvp_saved_total{intent="yes"} 892
voice_bridge_rsvp_saved_total{intent="no"} 342
```

## Deployment

### Dockerfile

```dockerfile
FROM node:20-alpine AS builder
WORKDIR /app
COPY package*.json ./
RUN npm ci --only=production
COPY . .
RUN npm run build  # If using TypeScript

FROM node:20-alpine
WORKDIR /app
COPY --from=builder /app /app
EXPOSE 4000
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
  CMD node --eval "require('http').get('http://localhost:4000/health', (r) => {process.exit(r.statusCode === 200 ? 0 : 1)})"
CMD ["node", "src/index.js"]
```

### Kubernetes Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: voice-bridge
  namespace: rsvp
  labels:
    app: voice-bridge
    region: il-central-1
spec:
  replicas: 2
  selector:
    matchLabels:
      app: voice-bridge
  template:
    metadata:
      labels:
        app: voice-bridge
        region: il-central-1
    spec:
      containers:
      - name: voice-bridge
        image: voice-bridge:latest
        ports:
        - containerPort: 4000
          name: ws
          protocol: TCP
        env:
        - name: GEMINI_API_KEY
          valueFrom:
            secretKeyRef:
              name: voice-bridge-secrets
              key: gemini-api-key
        - name: PHP_WEBHOOK
          value: https://kalfa.me/api/twilio/rsvp/process
        - name: CALL_LOG_URL
          value: https://kalfa.me/api/twilio/calling/log
        - name: CALL_LOG_SECRET
          valueFrom:
            secretKeyRef:
              name: voice-bridge-secrets
              key: call-log-secret
        - name: REGION
          value: il-central-1
        resources:
          requests:
            memory: "128Mi"
            cpu: "100m"
          limits:
            memory: "256Mi"
            cpu: "500m"
        livenessProbe:
          httpGet:
            path: /health
            port: 4000
          initialDelaySeconds: 10
          periodSeconds: 30
        readinessProbe:
          httpGet:
            path: /health
            port: 4000
          initialDelaySeconds: 5
          periodSeconds: 10
---
apiVersion: v1
kind: Service
metadata:
  name: voice-bridge
  namespace: rsvp
spec:
  type: ClusterIP
  ports:
  - port: 4000
    targetPort: 4000
    name: ws
  selector:
    app: voice-bridge
---
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: voice-bridge-ingress
  namespace: rsvp
  annotations:
    cert-manager.io/cluster-issuer: letsencrypt-prod
    nginx.ingress.kubernetes.io/websocket-services: voice-bridge
spec:
  ingressClassName: nginx
  tls:
  - hosts:
    - voice-bridge.kalfa.me
    secretName: voice-bridge-tls
  rules:
  - host: voice-bridge.kalfa.me
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: voice-bridge
            port:
              number: 4000
```

## Scaling

This service is **stateless** and can be horizontally scaled:

```bash
kubectl scale deployment voice-bridge --replicas=4
```

For autoscaling based on CPU:
```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: voice-bridge-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: voice-bridge
  minReplicas: 2
  maxReplicas: 10
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
```

## Monitoring

### Logs

Logs are emitted as JSON for easy parsing:

```json
{
  "timestamp": "2025-03-22T10:30:45.123Z",
  "level": "info",
  "message": "Call started",
  "context": {
    "client_id": "abc123",
    "call_sid": "CAXxx",
    "guest_name": "John Doe",
    "region": "il-central-1"
  }
}
```

### Metrics

The `/metrics` endpoint exposes:
- `voice_bridge_connections` - Active WebSocket connections
- `voice_bridge_calls_total` - Total calls processed (by region)
- `voice_bridge_rsvp_saved_total` - RSVPs saved (by intent)
- `voice_bridge_errors_total` - Errors (by type)
- `voice_bridge_latency_seconds` - Gemini API latency histogram

### Alerting (Prometheus)

```yaml
# Alert if error rate > 5%
- alert: VoiceBridgeHighErrorRate
  expr: |
    rate(voice_bridge_errors_total[5m]) / rate(voice_bridge_calls_total[5m]) > 0.05
  for: 5m
  labels:
    severity: warning
  annotations:
    summary: "Voice Bridge error rate above 5%"

# Alert if no connections (potential downtime)
- alert: VoiceBridgeNoConnections
  expr: voice_bridge_connections == 0
  for: 2m
  labels:
    severity: critical
  annotations:
    summary: "Voice Bridge has no active connections"
```

## Development

### Prerequisites

- Node.js 20+
- npm

### Setup

```bash
npm install
npm run dev  # Runs with hot-reload on port 4000
```

### Testing

```bash
npm test           # Unit tests
npm run test:e2e   # End-to-end tests with mock Twilio
npm run test:load  # Load tests
```

## Troubleshooting

### Common Issues

**Issue:** "Gemini API key not loaded"
- **Fix:** Set `GEMINI_API_KEY` environment variable

**Issue:** "Twilio connection closes immediately"
- **Fix:** Check that Twilio Media Stream URL includes correct host and port

**Issue:** "RSVP not being saved"
- **Fix:** Verify `PHP_WEBHOOK` URL is reachable and Laravel webhook is working

**Issue:** "High latency on responses"
- **Fix:** Check Gemini API status and network latency to Google servers

## License

Copyright © 2025 Kalfa RSVP. All rights reserved.
