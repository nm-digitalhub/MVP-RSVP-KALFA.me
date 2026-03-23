# Voice Bridge Migration Guide

## Migration from Monolith to Microservice

This guide covers migrating the Voice Bridge from the current Laravel-integrated deployment to a standalone microservice.

---

## Current State Assessment

### Current Architecture

```
kalfa.me (Single Server)
├── Laravel (PHP 8.4) - Port 8000
├── Reverb (WebSocket) - Port 6001
└── Node.js Voice Bridge - Port 4000 (via PM2/Systemd)
    └── Connects to:
        ├── Twilio Media Stream (wss://node.kalfa.me/media)
        ├── Gemini Live API (wss://generativelanguage.googleapis.com/...)
        └── Laravel Webhook (https://kalfa.me/api/twilio/rsvp/process)
```

### Current File Locations

| File | Location |
|------|----------|
| `server.js` | `/var/www/vhosts/kalfa.me/httpdocs/server.js` |
| `package.json` | `/var/www/vhosts/kalfa.me/httpdocs/package.json` |
| Environment | `/var/www/vhosts/kalfa.me/httpdocs/.env` |
| Process Manager | PM2 or systemd (check with `pm2 list` or `systemctl status voice-bridge`) |

---

## Migration Steps

### Phase 1: Preparation (Day 1)

#### 1.1 Create Separate Repository

```bash
# Create new repo
mkdir kalfa-rsvp-voice-bridge
cd kalfa-rsvp-voice-bridge

# Initialize
git init
npm init -y

# Copy source files
cp /var/www/vhosts/kalfa.me/httpdocs/server.js src/index.js
cp /var/www/vhosts/kalfa.me/httpdocs/package.json .
```

#### 1.2 Enhance Service with Production Features

Add to the new repository:

```bash
# Install additional dependencies
npm install --save pino pino-pretty prom-client
npm install --save-dev jest

# Create directory structure
mkdir -p src tests k8s docs
```

Required files (see templates in `/docs/microservices/voice-bridge/`):
- `Dockerfile` - Container image
- `src/health.js` - Health check endpoint
- `src/metrics.js` - Prometheus metrics
- `src/logger.js` - Structured logging
- `src/shutdown.js` - Graceful shutdown

#### 1.3 Build and Push Docker Image

```bash
# Build image
docker build -t ghcr.io/kalfa-rsvp/voice-bridge:v1.0.0 .

# Tag and push
docker tag ghcr.io/kalfa-rsvp/voice-bridge:v1.0.0 ghcr.io/kalfa-rsvp/voice-bridge:latest
docker push ghcr.io/kalfa-rsvp/voice-bridge:v1.0.0
docker push ghcr.io/kalfa-rsvp/voice-bridge:latest
```

---

### Phase 2: Infrastructure Setup (Day 2-3)

#### 2.1 Create Kubernetes Cluster (if not exists)

**Option A: Use existing cluster**
- Verify access: `kubectl get nodes`
- Create namespace: `kubectl apply -f k8s/namespace.yaml`

**Option B: Create new cluster**
```bash
# For Google Cloud
gcloud container clusters create voice-bridge \
  --region=europe-west1 \
  --num-nodes=2 \
  --machine-type=e2-medium

# Get credentials
gcloud container clusters get-credentials voice-bridge
```

#### 2.2 Create Secrets

```bash
# Create secrets from environment
kubectl create secret generic voice-bridge-secrets \
  --from-literal=gemini-api-key="$GEMINI_API_KEY" \
  --from-literal=call-log-secret="$CALL_LOG_SECRET" \
  -n rsvp

# Verify
kubectl get secret voice-bridge-secrets -n rsvp
```

#### 2.3 Deploy to Kubernetes

```bash
# Apply all manifests
kubectl apply -f k8s/

# Verify deployment
kubectl get deployment voice-bridge -n rsvp
kubectl get pods -n rsvp
kubectl get svc voice-bridge -n rsvp
```

#### 2.4 Configure Ingress

```bash
# Apply ingress
kubectl apply -f k8s/ingress.yaml

# Get load balancer IP
kubectl get ingress voice-bridge-ingress -n rsvp

# Update DNS (A record)
# voice-bridge.kalfa.me → LB_IP
```

---

### Phase 3: Traffic Migration (Day 4)

#### 3.1 DNS Update Strategy

**Option A: Blue-Green (Recommended)**

1. **Deploy new service parallel:**
   - Current: `node.kalfa.me` → Current Node.js
   - New: `voice-bridge.kalfa.me` → Kubernetes

2. **Test new service:**
```bash
# Test WebSocket connection
wscat -c "wss://voice-bridge.kalfa.me/media?guest_name=Test"

# Test health check
curl https://voice-bridge.kalfa.me/health
```

3. **Update Twilio to point to new URL:**
   - Old: `wss://node.kalfa.me/media`
   - New: `wss://voice-bridge.kalfa.me/media`

4. **Monitor for 24 hours**, then remove old service

**Option B: Weighted DNS (Not recommended for WebSocket)**

Use Cloudflare Load Balancer with weighted pools (may cause connection drops).

#### 3.2 Rollback Plan

If issues occur:

```bash
# Revert Twilio URL to old endpoint
# Update TwiML bin to use: wss://node.kalfa.me/media

# Scale down new deployment
kubectl scale deployment voice-bridge --replicas=0 -n rsvp
```

---

### Phase 4: Validation (Day 5)

#### 4.1 Smoke Tests

```bash
# 1. Health check
curl https://voice-bridge.kalfa.me/health

# 2. Metrics endpoint
curl https://voice-bridge.kalfa.me/metrics

# 3. WebSocket connection (with test parameters)
wscat -c "wss://voice-bridge.kalfa.me/media?guest_name=Test%20User&event_name=Test%20Event"
```

#### 4.2 Integration Tests

```javascript
// tests/integration/twilio-flow.test.js
describe('Twilio Integration', () => {
  test('handles full call flow', async () => {
    const ws = new WebSocket('wss://voice-bridge.kalfa.me/media?' +
      'guest_name=Test&event_name=Test');

    await waitForMessage(ws, 'event=start');

    // Send audio
    ws.send(JSON.stringify({
      event: 'media',
      media: { payload: base64Audio }
    }));

    // Verify Gemini response
    await waitForMessage(ws, 'event=media');

    ws.close();
  });
});
```

#### 4.3 Load Testing

```bash
# Use k6 or Artillery
k6 run --vus 10 --duration 5m tests/load/websocket-flow.js
```

---

### Phase 5: Cleanup (Day 6)

#### 5.1 Stop Old Service

```bash
# Stop PM2 process (if using PM2)
pm2 stop voice-bridge
pm2 delete voice-bridge

# Or stop systemd service (if using systemd)
sudo systemctl stop voice-bridge
sudo systemctl disable voice-bridge

# Verify stopped
pm2 list
# or
systemctl status voice-bridge
```

#### 5.2 Remove Old Files

```bash
# Optional: Move to backup location
mv /var/www/vhosts/kalfa.me/httpdocs/server.js \
   /var/www/vhosts/kalfa.me/httpdocs/server.js.backup

# Or remove entirely (after confirming no issues)
rm /var/www/vhosts/kalfa.me/httpdocs/server.js
```

---

## Configuration Reference

### Old Environment Variables (.env)

```env
GEMINI_API_KEY=AIzaSy...
GEMINI_LIVE_MODEL=models/gemini-2.0-flash-exp
PHP_WEBHOOK=https://kalfa.me/api/twilio/rsvp/process
CALL_LOG_URL=https://kalfa.me/api/twilio/calling/log
TWILIO_AUTH_TOKEN=...
RSVP_NODE_WS_URL=wss://node.kalfa.me/media
```

### New Environment Variables (Kubernetes)

Same variables, but managed via ConfigMap/Secret:

```yaml
# ConfigMap
apiVersion: v1
kind: ConfigMap
data:
  GEMINI_MODEL: "models/gemini-2.0-flash-exp"
  PHP_WEBHOOK: "https://kalfa.me/api/twilio/rsvp/process"
  CALL_LOG_URL: "https://kalfa.me/api/twilio/calling/log"
  LOG_LEVEL: "info"
  REGION: "il-central-1"

---
# Secret
apiVersion: v1
kind: Secret
stringData:
  GEMINI_API_KEY: "AIzaSy..."
  CALL_LOG_SECRET: "..."
```

---

## Verification Checklist

### Pre-Migration

- [ ] Backup current `server.js`
- [ ] Document current PM2/systemd config
- [ ] Test new Docker image locally
- [ ] Prepare rollback plan

### During Migration

- [ ] Kubernetes cluster ready
- [ ] Secrets created
- [ ] Deployment successful
- [ ] Ingress configured
- [ ] DNS updated to point to new service
- [ ] Health checks passing

### Post-Migration

- [ ] Monitoring setup (Prometheus, Grafana)
- [ ] Alerts configured
- [ ] Logs streaming to centralized logging
- [ ] Load test successful
- [ ] Old service stopped
- [ ] Documentation updated

---

## Troubleshooting

### Issue: WebSocket Connections Dropping

**Symptoms:** Connections close after 30-60 seconds

**Solutions:**
```yaml
# Increase proxy timeouts in Ingress
nginx.ingress.kubernetes.io/proxy-read-timeout: "3600"
nginx.ingress.kubernetes.io/proxy-send-timeout: "3600"
```

### Issue: High Memory Usage

**Symptoms:** Pods being OOMKilled

**Solutions:**
```yaml
# Increase memory limits
resources:
  limits:
    memory: "512Mi"
```

### Issue: Gemini API Timeouts

**Symptoms:** Errors connecting to Gemini

**Solutions:**
- Check network policies allow egress to `generativelanguage.googleapis.com:443`
- Verify `GEMINI_API_KEY` is correct
- Check API quota limits

---

## Rollback Procedure

### Immediate Rollback (if critical issues)

```bash
# 1. Update Twilio URL back to old endpoint
# Via Twilio Console or API

# 2. Scale down new deployment
kubectl scale deployment voice-bridge --replicas=0 -n rsvp

# 3. Restart old service
pm2 resurrect  # If using PM2
# or
sudo systemctl start voice-bridge  # If using systemd

# 4. Verify old service running
pm2 list
curl http://localhost:4000/health
```

### Rollback After DNS Propagation

If DNS has already propagated:

```bash
# Update DNS to point back to old server
# A record: voice-bridge.kalfa.me → OLD_SERVER_IP

# Wait for TTL (usually 300s)
# Verify connections going to old service
```

---

## Success Criteria

Migration is successful when:

1. ✅ New service responds to health checks
2. ✅ WebSocket connections accepted from Twilio
3. ✅ Gemini API integration working
4. ✅ RSVP webhook posting successfully
5. ✅ Metrics collected by Prometheus
6. ✅ No increase in error rates vs. baseline
7. ✅ Old service safely stopped
8. ✅ Documentation updated

---

## Next Steps After Migration

1. **Enable HPA** for automatic scaling
2. **Set up multi-region deployment** (per multi-region architecture)
3. **Implement circuit breaker** for webhook calls
4. **Add distributed tracing** (OpenTelemetry)
5. **Create runbooks** for common incidents
6. **Train ops team** on new deployment procedures
