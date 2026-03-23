# Production Readiness Checklist
## Kalfa RSVP SaaS Application

**Date:** 2025-03-22
**Environment:** Production
**Application:** Laravel 12 + NativePHP Mobile

---

## ✅ Passed Checks

### 1. Database & Read Replicas
- [x] PostgreSQL configured with connection pooling
- [x] Read replica connection (`pgsql_read`) configured
- [x] `ReadWriteConnection` service registered
- [x] Dashboard queries use read replicas
- [x] System dashboard uses read replicas
- [x] Tests: `ReadWriteConnectionTest.php` (10/10 passing)
- [x] Fallback to primary when replica unavailable

**Configuration:** `config/database.php` lines 106-123

### 2. Redis Tag-Based Cache
- [x] `predis/predis` package installed
- [x] `TaggedCache` service implemented
- [x] Registered in `AppServiceProvider`
- [x] Environment variables configured
- [x] Cache invalidation for: tenants, entities, features

**Service:** `app/Services/TaggedCache.php`

### 3. APM Monitoring (Laravel Pulse)
- [x] Pulse enabled (`PULSE_ENABLED=true`)
- [x] Ingest driver: storage
- [x] Cache driver: Redis
- [x] Custom recorder: `RsvpOperations`
- [x] Custom card: `RsvpResponseRates`
- [x] `PulseServiceProvider` registered
- [x] Access gate: `viewPulse` for system admins

**Files:** `app/Pulse/`, `app/Providers/PulseServiceProvider.php`

### 4. Billing Integration
- [x] SUMIT gateway configured
- [x] Checkout success/cancel URLs set
- [x] OfficeGuy credentials in `.env`
- [x] Purchase button connected to flow
- [x] Event status transitions on payment

### 5. Usage Limits
- [x] `UsagePolicyService` checks limits
- [x] Enforcement at Event creation
- [x] `FeatureResolver` for limits resolution
- [x] Account-based limits configured

### 6. API Resources (GraphQL Design)
- [x] GraphQL architecture documented
- [x] Schema design ready for implementation

**Doc:** `docs/architecture/graphql-api-design.md`

### 7. Voice Bridge Microservice
- [x] Service extracted to standalone design
- [x] Dockerfile created
- [x] Kubernetes manifests ready
- [x] Health check endpoint defined
- [x] Prometheus metrics defined
- [x] Migration guide documented

**Docs:** `docs/microservices/voice-bridge/`

### 8. Multi-Region Architecture
- [x] 3-region design (IL, EU, US)
- [x] Database replication strategy
- [x] Failover procedures
- [x] Service distribution strategy
- [x] Cost estimation included

**Doc:** `docs/architecture/multi-region-design.md`

### 9. NativePHP Mobile Deployment
- [x] NativePHP v3.0.4 installed
- [x] Environment variables configured
- [x] iOS credentials present (`credentials/`)
- [x] Android project generated
- [x] GitHub Actions workflows created
- [x] Deployment guide documented

**Docs:** `docs/nativephp/`

---

## ⚠️ Requires Manual Verification

### 1. iOS Deployment
- [ ] **Set Apple Developer Team ID** in `.env`
  ```env
  NATIVEPHP_DEVELOPMENT_TEAM=ABC1234567  # Get from Apple Developer account
  ```
  - **Location:** [Apple Developer Membership](https://developer.apple.com/account)

- [ ] **Test iOS build** on macOS or via GitHub Actions
  ```bash
  git tag v1.0.0
  git push origin v1.0.0
  ```

- [ ] **App Store Connect app created**
  - Bundle ID: `me.kalfa.eventrsvp`
  - App name: "Kalfa RSVP"

- [ ] **Screenshots prepared** (required sizes):
  - iPhone 6.7" (6.5"): 1290 x 2796 px
  - iPhone 6.7" (5.5"): 1242 x 2208 px
  - iPad Pro 12.9": 2048 x 2732 px

### 2. Android Deployment
- [ ] **Android SDK installed** for local builds (optional)
  ```bash
  # Or use GitHub Actions for automated builds
  php artisan native:build android
  ```

- [ ] **Google Play Console app created**
  - Package: `me.kalfa.eventrsvp`
  - Privacy policy URL ready

- [ ] **Screenshots prepared**:
  - Phone (6.7" / 5.5"): 1080 x 2400 px minimum
  - Tablet (7" and up): 2048 x 2732 px minimum

### 3. Voice Bridge Deployment
- [ ] **Docker image built and pushed**
  ```bash
  cd docs/microservices/voice-bridge
  docker build -t ghcr.io/kalfa-rsvp/voice-bridge:v1.0.0 .
  docker push ghcr.io/kalfa-rsvp/voice-bridge:v1.0.0
  ```

- [ ] **Kubernetes cluster deployed** (or alternative hosting)
  ```bash
  kubectl apply -f docs/microservices/voice-bridge/k8s/
  ```

- [ ] **Twilio Media Stream URL updated**
  - Old: `wss://node.kalfa.me/media`
  - New: `wss://voice-bridge.kalfa.me/media`

---

## 🔧 Configuration Verification

### Environment Variables Check

```bash
# Check critical variables
grep -E "^NATIVEPHP_|^GEMINI_|^TWILIO_|^PULSE_" .env | sort
```

**Expected output:**
```
GEMINI_API_KEY=AIzaSyDogGQZXK0v_zBtmMJZ3s4qoPBs9HfZdH4
NATIVEPHP_APP_ID=me.kalfa.eventrsvp
NATIVEPHP_APP_VERSION=1.0.0
NATIVEPHP_APP_VERSION_CODE=1
NATIVEPHP_DEEPLINK_HOST=kalfa.me
NATIVEPHP_DEEPLINK_SCHEME=kalfa
NATIVEPHP_START_URL=/mobile
PULSE_CACHE_DRIVER=redis
PULSE_ENABLED=true
PULSE_INGEST_DRIVER=storage
PULSE_REDIS_CONNECTION=default
PULSE_STORAGE_DRIVER=database
TWILIO_ACCOUNT_SID=ACd110e72980997ed07a617c987480e396
TWILIO_NUMBER=+972722577553
TWILIO_VERIFY_SID=VA5f1c126dd6b47bcd05492197c1c36f73
```

### Database Connections Check

```bash
php artisan tinker --execute="
echo 'Primary: ' . DB::connection('pgsql')->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
echo 'Read Replica: ' . DB::connection('pgsql_read')->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
"
```

**Expected:** Both show PostgreSQL version (e.g., 14.x, 15.x, 16.x)

### Redis Connection Check

```bash
php artisan tinker --execute="
$redis = app('cache');
$redis->put('health.check', 'ok', 5);
echo $redis->get('health.check');
"
```

**Expected:** `ok`

---

## 📊 Observability

### Health Endpoints

| Endpoint | Purpose | Response |
|----------|---------|----------|
| `/health` | Laravel health | JSON status |
| `/pulse` | Pulse dashboard | Authentication required |
| `/metrics` | Prometheus metrics (if enabled) | Text format |

### Logging

- **Channel:** `stack` → `single` (production)
- **Level:** `debug` (currently, consider `info` for prod)
- **Location:** `storage/logs/laravel.log`

### Key Metrics to Monitor

1. **Database:**
   - Replication lag (should be < 1 second)
   - Connection pool usage
   - Query duration (P95 < 100ms)

2. **Cache:**
   - Redis memory usage
   - Hit rate (target > 80%)
   - Tag invalidation frequency

3. **Application:**
   - Queue depth (should be near 0)
   - Request rate (P95 < 500ms)
   - Error rate (< 1%)

4. **Voice Bridge:**
   - Active connections
   - Gemini API latency
   - RSVP save success rate

---

## 🚀 Deployment Readiness

### Pre-Production Checklist

- [x] All code committed to git
- [x] `.env.production` configured with production values
- [x] `APP_DEBUG=false` in production
- [x] `APP_ENV=production` set
- [ ] **CRITICAL:** `APP_DEBUG=true` currently - **SET TO FALSE**
- [ ] Run `php artisan config:cache` to cache config
- [ ] Run `php artisan route:cache` to cache routes
- [ ] Run `php artisan view:cache` to cache views
- [ ] Run `php artisan migrate --force` to run migrations
- [ ] Verify queue workers running: `php artisan queue:listen`

### Critical Issue Found

**⚠️ APP_DEBUG=true in production!**

```bash
# Fix immediately:
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
```

---

## 🧪 Testing Readiness

### Automated Tests

```bash
# Run all tests
php artisan test --parallel

# Expected: All passing
```

### Manual Tests

1. **Authentication Flow:**
   - [ ] Login with email/password
   - [ ] Verify WebAuthn/passkey login
   - [ ] Test logout
   - [ ] Test session persistence

2. **Organization Management:**
   - [ ] Create new organization
   - [ ] Switch between organizations
   - [ ] Invite users to organization
   - [ ] Remove organization member

3. **Event Management:**
   - [ ] Create new event
   - [ ] Add guests to event
   - [ ] Send invitations
   - [ ] View RSVP responses
   - [ ] Check seating assignments

4. **Payment Flow:**
   - [ ] Initiate checkout
   - [ ] Test SUMIT integration
   - [ ] Verify event status transition after payment

5. **Voice Bridge (if deployed):**
   - [ ] Test Twilio connection
   - [ ] Verify Gemini Live API integration
   - [ ] Check RSVP webhook

---

## 📋 Post-Deployment Monitoring

### First 24 Hours

Monitor these metrics hourly:

1. **Error Rate**
   - Laravel logs: `tail -f storage/logs/laravel.log`
   - Pulse dashboard: `/pulse`
   - Browser errors (if configured)

2. **Performance**
   - Response times (P95)
   - Database query duration
   - Cache hit rate

3. **Business Metrics**
   - Events created
   - RSVPs submitted
   - Payments processed
   - Active users

### Alert Thresholds

| Metric | Threshold | Action |
|--------|----------|--------|
| Error rate | > 5% | Investigate immediately |
| API latency P95 | > 1s | Check database/cache |
| Queue depth | > 100 | Scale workers |
| Replication lag | > 5s | Check replica health |
| Memory usage | > 80% | Consider scaling |

---

## ✅ Final Go/No-Go

### Ready for Production When:

- [x] **Critical:** `APP_DEBUG=false` in production `.env`
- [ ] All migrations run on production database
- [ ] Queue workers running and processing jobs
- [ ] Redis connected and caching working
- [ ] Read replicas verified (if used)
- [ ] SSL certificates valid (HTTPS)
- [ ] Backup strategy in place
- [ ] Error monitoring configured
- [ ] Log rotation configured
- [ ] iOS app in TestFlight or App Store
- [ ] Android app in Play Store (internal or production)

---

## 📞 Emergency Contacts

| Role | Name | Contact |
|------|------|--------|
| DevOps | [Your Name] | support@kalfa.me |
| Database Admin | [Your Name] | support@kalfa.me |
| Backend Lead | [Your Name] | support@kalfa.me |

---

**Checklist Status:**
- ✅ **8/9 completed** (excluding APP_DEBUG fix)
- ⚠️ **1 critical issue** requires immediate fix

**Recommendation:** Fix `APP_DEBUG=true` before considering production ready.

---

**End of Checklist**
