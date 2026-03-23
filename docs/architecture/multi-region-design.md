# Multi-Region Architecture Design
## RSVP+Seating SaaS Application

**Version:** 1.0
**Date:** 2025-03-22
**Status:** Design Document

---

## 1. Executive Summary

This document outlines a multi-region architecture strategy for the RSVP+Seating SaaS application to achieve:
- **Low latency** for users across geographic regions
- **High availability** with regional failover capability
- **Data residency** compliance with regional regulations
- **Scalability** across multiple availability zones

### Target Regions (Phase 1)

| Region | Location | Primary Use | Data Residency |
|--------|----------|-------------|----------------|
| `il-central-1` | Tel Aviv, Israel | Primary region | Israeli users, Hebrew content |
| `eu-west-1` | Frankfurt, Germany | EU expansion | EU users (GDPR compliance) |
| `us-east-1` | Virginia, USA | US expansion | US/Canada users |

---

## 2. Current Architecture Analysis

### Current Deployment

```
┌─────────────────────────────────────────────────────────────┐
│                     kalfa.me (Single Region)                │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Laravel    │  │   Node.js    │  │  PostgreSQL   │      │
│  │   (PHP 8.4)  │  │  Voice Bridge│  │   Primary     │      │
│  │   Port 8000  │  │   Port 6001  │  │   Port 5432   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
│         │                  │                  │              │
│         └──────────────────┴──────────────────┘              │
│                      Nginx/Caddy                             │
│                      Port 443/80                             │
└─────────────────────────────────────────────────────────────┘
```

### Current Services

| Service | Technology | Scope | Replication |
|---------|------------|-------|-------------|
| Laravel API | PHP 8.4 / Laravel 12 | Core application | ❌ No |
| PostgreSQL | v14+ | Primary database | ❌ No (local read replica planned) |
| Node.js Voice Bridge | Custom | Twilio ↔ Gemini relay | ❌ No |
| Redis | v7+ | Cache/Queue | ❌ No |
| Reverb | Laravel WebSocket | Real-time | ❌ No |

---

## 3. Multi-Region Target Architecture

### 3.1 Architecture Diagram

```
                           ┌───────────────────┐
                           │   Cloudflare CDN  │
                           │  (Distributed)    │
                           └─────────┬─────────┘
                                     │
                    ┌────────────────┴────────────────┐
                    │                                     │
         ┌──────────▼──────────┐              ┌─────────▼─────────┐
         │  Route 53 / GeoDNS  │              │   Load Balancer  │
         │  (Geo-based routing)│              │  (Regional LB)   │
         └──────────┬──────────┘              └─────────┬─────────┘
                    │                                     │
    ┌───────────────┼───────────────┐                     │
    │               │               │                     │
┌───▼────┐    ┌────▼────┐    ┌────▼────┐         ┌───────▼───────┐
│ Israel │    │  EU     │    │   US    │         │  Fallback     │
│ Region │    │ Region  │    │ Region  │         │  Strategy     │
│ (IL)   │    │ (DE)    │    │ (VA)    │         │               │
└───┬────┘    └────┬────┘    └────┬────┘         └───────────────┘
    │              │              │
    │         ┌────▼────┐         │
    │         │ Latency │         │
    │         │ Based   │         │
    │         │ Routing │         │
    │         └────┬────┘         │
    │              │              │
┌───▼──────────────────▼────────────▼─────┐
│           Regional Components             │
├─────────────────────────────────────────┤
│  ┌─────────┐  ┌─────────┐  ┌─────────┐ │
│  │ Laravel │  │  Redis  │  │  Reverb │ │
│  │   API   │  │  Cache  │  │  WS     │ │
│  │ (Pods)  │  │         │  │         │ │
│  └────┬────┘  └────┬────┘  └────┬────┘ │
│       │            │            │       │
│  ┌────▼────┐  ┌────▼────┐  ┌────▼────┐ │
│  │PostgreSQL│ │Node.js  │  │Storage  │ │
│  │ Primary  │ │Voice    │  │S3/CDN   │ │
│  │+ Replica │ │Bridge   │  │         │ │
│  └──────────┘  └─────────┘  └─────────┘ │
└─────────────────────────────────────────┘
```

### 3.2 Regional Components

**Per Region:**

| Component | Technology | HA Setup | Cross-Region Sync |
|-----------|------------|----------|-------------------|
| Laravel API | PHP 8.4 / Laravel 12 | 2+ pods (Kubernetes) | ❌ Stateless |
| PostgreSQL | v14+ | Primary + 1-2 replicas | ✅ Logical replication |
| Redis | v7+ | Redis Sentinel | ❌ Region-local cache |
| Node.js Voice Bridge | Custom | 2+ instances | ❌ Region-local |
| Reverb | Laravel WebSocket | 2+ instances | ❌ Region-local |
| Storage | S3-compatible | ✅ Built-in | ✅ Cross-region replication |

---

## 4. Database Replication Strategy

### 4.1 Primary-Replica Topology

```
                    ┌─────────────────────────────────┐
                    │     Global Write Coordinator    │
                    │        (il-central-1)           │
                    └────────────┬────────────────────┘
                                 │
                    ┌────────────▼────────────────────┐
                    │      PostgreSQL Primary         │
                    │      (il-central-1)             │
                    │      Active-Active Ready        │
                    └────────────┬────────────────────┘
                                 │
         ┌───────────────────────┼───────────────────────┐
         │                       │                       │
    ┌────▼────┐           ┌─────▼─────┐          ┌────▼────┐
    │   IL    │           │    EU     │          │   US    │
    │ Replica │           │  Replica  │          │ Replica │
    │ (Async) │           │  (Async)  │          │ (Async) │
    └────┬────┘           └─────┬─────┘          └────┬────┘
         │                      │                      │
    ┌────▼────┐           ┌─────▼─────┐          ┌────▼────┐
    │ Read    │           │  Read     │          │  Read   │
    │ Queries │           │  Queries  │          │ Queries │
    │(Dashboard)│          │(Dashboard)│         │(Dashboard)│
    └─────────┘           └───────────┘          └─────────┘
```

### 4.2 Replication Configuration

**PostgreSQL Logical Replication:**

```sql
-- On Primary (il-central-1)
CREATE PUBLICATION rsvp_publication FOR TABLE organizations, users, events, guests, invitations;

-- On Replicas
CREATE SUBSCRIPTION rsvp_subscription
CONNECTION 'host=il-central-1-db.kalfa.me dbname=kalfa_rsvp user=replicator password=xxx'
PUBLICATION rsvp_publication
WITH (create_slot = false);
```

**Replication Lag Monitoring:**

```php
// app/Services/Database/ReplicationMonitor.php
class ReplicationMonitor
{
    public function getLagSeconds(string $region): int
    {
        $result = DB::connection("pgsql_read_{$region}")
            ->select('SELECT EXTRACT(EPOCH FROM (now() - pg_last_xact_replay_timestamp())) as lag');

        return (int) $result[0]->lag;
    }
}
```

### 4.3 Write Routing

**All writes go to Primary:**

```php
// config/database.php per region
'connections' => [
    'pgsql' => [
        'write' => [
            'host' => env('DB_PRIMARY_HOST', 'il-central-1-db.kalfa.me'),
        ],
        'read' => [
            'host' => [
                env('DB_REPLICA_HOST', 'localhost'),  // Local replica first
                env('DB_PRIMARY_HOST'),                 // Fallback to primary
            ],
        ],
        'sticky' => true,  // Use write connection for reads in same request
    ],
],
```

---

## 5. Service Distribution Strategy

### 5.1 Stateless Services (Multi-Region Active-Active)

**Services:**
- Laravel API
- Redis (region-local cache)
- Reverb (WebSocket)
- Node.js Voice Bridge

**Strategy:**
```
User Request → Cloudflare → GeoDNS → Regional LB → Regional Laravel Pods
                                    ↓
                             Region-local Redis
```

**Configuration per region (`.env.region`):**
```env
# il-central-1
APP_REGION=il-central-1
APP_REGION_NAME=Israel
APP_TIMEZONE=Asia/Jerusalem
APP_LOCALE=he
DB_PRIMARY_HOST=il-central-1-db.kalfa.me
DB_REPLICA_HOST=il-central-1-replica.kalfa.me
REDIS_HOST=il-central-1-redis.kalfa.me

# eu-west-1
APP_REGION=eu-west-1
APP_REGION_NAME=Europe
APP_TIMEZONE=Europe/Berlin
APP_LOCALE=en
DB_PRIMARY_HOST=il-central-1-db.kalfa.me  # Cross-region write
DB_REPLICA_HOST=eu-west-1-replica.kalfa.me
REDIS_HOST=eu-west-1-redis.kalfa.me
```

### 5.2 Stateful Services

**PostgreSQL:**
- **Primary:** il-central-1 (single writer for consistency)
- **Replicas:** One per region for reads
- **Failover:** Manual promotion of replica if primary fails

**Storage (S3-compatible):**
- **Cross-region replication:** Enabled
- **CDN:** Cloudflare or CloudFront
- **Lifecycle rules:** Move old media to Glacier

---

## 6. Failover Strategy

### 6.1 Regional Failover

**Scenario:** Entire region (il-central-1) goes down

**Procedure:**

1. **Health Check (Automated):**
```php
// app/Services/Health/RegionalHealthCheck.php
class RegionalHealthCheck
{
    public function isRegionHealthy(string $region): bool
    {
        $checks = [
            $this->checkDatabase($region),
            $this->checkCache($region),
            $this->checkApi($region),
        ];

        return all($checks);
    }
}
```

2. **DNS Failover (Automated):**
```yaml
# Route 53 Health Checks
Type: HTTPS
Resource: https://il-central-1.kalfa.me/health
Interval: 30s
Threshold: 3 failures → Failover to eu-west-1
```

3. **Database Promotion (Manual/Semi-Automated):**
```bash
# Promote EU replica to primary
pg_ctl promote -D /var/lib/postgresql/data

# Update DNS to point DB_PRIMARY_HOST to EU
# Update Laravel config in all regions
```

### 6.2 Graceful Degradation

**Read Mode:**
- If replica is down → Read from primary (with degraded performance)
- If primary is down → Read-only mode from replica (stale data)

**Write Mode:**
- If primary is down → Queue writes for later replay
- Return 503 Service Unavailable with Retry-After header

---

## 7. Session & State Management

### 7.1 Sticky Sessions vs Distributed Sessions

**Decision: Distributed Sessions (Redis)**

**Reason:**
- Users may access from different regions (travel, VPN)
- Failover between regions should preserve sessions
- WebSocket connections (Reverb) need consistent backend

**Implementation:**
```env
SESSION_DRIVER=redis
SESSION_CONNECTION=regional  // Primary region for all writes
REDIS_CACHE_HOST=redis-session.kalfa.me  // Single-tenant Redis for sessions
```

### 7.2 WebSocket (Reverb)

**Challenge:** Reverb connections are stateful

**Solution:**
- Regional Reverb servers for low latency
- Cross-region Redis for channel broadcasting
- Sticky routing via X-Forwarded-For header

---

## 8. API Gateway & Routing

### 8.1 Single Entry Point

```
api.kalfa.me → Cloudflare → Regional API Gateway
```

**Regional Gateway Logic:**
```php
// app/Http/Middleware/RegionRouting.php
class RegionRouting
{
    public function handle(Request $request, Closure $next)
    {
        $userRegion = $this->detectUserRegion($request);

        // API requests route to nearest healthy region
        $targetRegion = $this->selectRegion($userRegion);

        if ($targetRegion !== config('app.region')) {
            return $this->proxyToRegion($request, $targetRegion);
        }

        return $next($request);
    }

    private function selectRegion(string $userRegion): string
    {
        $healthyRegions = $this->getHealthyRegions();

        // Prefer user's region if healthy
        if (in_array($userRegion, $healthyRegions)) {
            return $userRegion;
        }

        // Otherwise, nearest healthy region
        return $this->nearestRegion($userRegion, $healthyRegions);
    }
}
```

### 8.2 Geographic Routing

**GeoDNS Configuration:**
```yaml
# Route 53
kalfa.me:
  - IL: A → il-central-1.kalfa.me
  - EU: A → eu-west-1.kalfa.me
  - US: A → us-east-1.kalfa.me
  - Default: A → il-central-1.kalfa.me
```

---

## 9. Deployment Pipeline

### 9.1 Multi-Region CI/CD

```yaml
# .github/workflows/deploy-regional.yml
name: Regional Deployment

on:
  push:
    branches: [main]

jobs:
  deploy:
    strategy:
      matrix:
        region: [il-central-1, eu-west-1, us-east-1]

    steps:
      - name: Deploy to ${{ matrix.region }}
        run: |
          # Set region-specific env
          cp .env.${{ matrix.region }} .env

          # Deploy to regional Kubernetes cluster
          kubectl config use-context ${{ matrix.region }}
          kubectl apply -f kubernetes/

          # Run migrations on primary only
          if [ "${{ matrix.region }}" == "il-central-1" ]; then
            php artisan migrate --force
          fi
```

### 9.2 Configuration Management

**Hierarchy:**
1. `.env` - Base configuration
2. `.env.production` - Production overrides
3. `.env.${REGION}` - Region-specific (DB hosts, Redis, etc.)
4. Runtime config - Per-request region context

---

## 10. Cost Estimation

### 10.1 Infrastructure Costs (Monthly)

| Service | IL Region | EU Region | US Region | Total |
|---------|-----------|-----------|-----------|-------|
| Kubernetes (4 vCPU each) | $120 | $150 | $140 | $410 |
| PostgreSQL (Primary) | $200 | - | - | $200 |
| PostgreSQL (Replicas) | $100 | $150 | $140 | $390 |
| Redis (Sentinel) | $50 | $60 | $55 | $165 |
| Storage (S3 + CDN) | $80 | $80 | $80 | $240 |
| Voice Bridge VM | $40 | $50 | $45 | $135 |
| **Monthly Total** | **$590** | **$490** | **$460** | **$1,540** |

**Annual:** ~$18,480

### 10.2 Cost Optimization

1. **Reserved Instances:** 20-30% savings for 1-year commitment
2. **Spot Instances:** For non-critical workloads (reporting jobs)
3. **Autoscaling:** Scale down during off-hours
4. **CDN Caching:** Reduce API calls by 40-60%

---

## 11. Migration Path

### Phase 1: Foundation (Week 1-2)
- [ ] Set up regional DNS/Load Balancer
- [ ] Deploy PostgreSQL replicas in EU/US
- [ ] Test replication lag monitoring
- [ ] Implement regional health checks

### Phase 2: Application Readiness (Week 3-4)
- [ ] Add region detection middleware
- [ ] Update database config for read/write splitting
- [ ] Implement cross-region Redis for sessions
- [ ] Update Laravel for region-aware URLs

### Phase 3: Regional Deployment (Week 5-6)
- [ ] Deploy Laravel pods to EU region
- [ ] Deploy Laravel pods to US region
- [ ] Configure regional Redis/Reverb
- [ ] Test failover procedures

### Phase 4: Cutover (Week 7-8)
- [ ] Enable GeoDNS routing (10% → 50% → 100%)
- [ ] Monitor metrics (latency, error rates)
- [ ] Fine-tune routing rules
- [ ] Document runbooks for ops team

---

## 12. Monitoring & Observability

### 12.1 Per-Region Metrics

**Laravel Pulse Cards:**
- Regional health status
- Cross-region latency
- Replication lag by region
- Request volume by region

**Dashboard:**
```php
// app/Livewire/System/MultiRegionDashboard.php
class MultiRegionDashboard extends Component
{
    public function render()
    {
        return view('livewire.system.multi-region', [
            'regions' => [
                'il-central-1' => [
                    'status' => 'healthy',
                    'latency' => '12ms',
                    'db_lag' => '0.1s',
                    'requests_per_min' => 1450,
                ],
                'eu-west-1' => [
                    'status' => 'healthy',
                    'latency' => '45ms',
                    'db_lag' => '0.8s',
                    'requests_per_min' => 320,
                ],
                'us-east-1' => [
                    'status' => 'degraded',
                    'latency' => '120ms',
                    'db_lag' => '2.3s',
                    'requests_per_min' => 180,
                ],
            ],
        ]);
    }
}
```

### 12.2 Alerting

**Critical Alerts:**
- Region down (5min)
- Replication lag > 5 seconds
- Primary database unreachable
- Voice Bridge offline in any region

---

## 13. Security Considerations

### 13.1 Cross-Region Communication

- **TLS Everywhere:** All inter-region traffic encrypted
- **VPN:** Private links between regions (AWS Direct Connect / Azure ExpressRoute)
- **Firewall:** Whitelist only necessary ports between regions

### 13.2 Data Residency

**Israeli User Data:**
- Primary storage in il-central-1
- Replicas allowed for read-only (consult legal)
- No cross-border transfer without user consent

**EU User Data (GDPR):**
- Primary storage still in il-central-1 (current)
- Plan for EU primary region if user base grows
- Data export capability per GDPR Article 15

---

## 14. Appendices

### A. Region Codes

| Code | Name | Provider | Location |
|------|------|----------|----------|
| `il-central-1` | Israel Central | Azure/Google | Tel Aviv |
| `eu-west-1` | EU West | AWS | Frankfurt |
| `us-east-1` | US East | AWS | Virginia |

### B. DNS Records

```
# Primary
kalfa.me              A      (GeoDNS)
api.kalfa.me          CNAME  (GeoDNS)
ws.kalfa.me           CNAME  (GeoDNS)

# Regional (direct access)
il.kalfa.me           A      192.0.2.10
eu.kalfa.me           A      192.0.2.20
us.kalfa.me           A      192.0.2.30

# Database
db.kalfa.me           CNAME  il-central-1-db.kalfa.me
db-replica.il.kalfa.me  CNAME  il-central-1-replica.kalfa.me
db-replica.eu.kalfa.me  CNAME  eu-west-1-replica.kalfa.me
```

### C. Failover Runbook

**Scenario: il-central-1 Complete Outage**

1. **Detection (5 min)**
   - Alerts fire from multiple monitoring points
   - Health checks fail for all components

2. **Assessment (10 min)**
   - Confirm regional failure (not just individual component)
   - Check status of eu-west-1 and us-east-1

3. **Failover Initiation (15 min)**
   - Update GeoDNS to route 100% to eu-west-1
   - Promote eu-west-1 replica to primary (read-write mode)

4. **Validation (20 min)**
   - Verify writes succeeding in EU region
   - Check replication to US region
   - Test critical user flows

5. **Recovery (Post-Incident)**
   - Investigate root cause in il-central-1
   - Repair and restore as replica
   - Eventually restore as primary (planned cutover)

---

**Document Version:** 1.0
**Last Updated:** 2025-03-22
**Next Review:** After Phase 1 completion
