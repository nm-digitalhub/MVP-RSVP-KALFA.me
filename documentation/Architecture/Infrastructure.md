---
date: 2026-03-16
tags: [architecture, infrastructure, deployment, development]
status: active
---

# Infrastructure & Deployment

## Development Setup

### Prerequisites
- PHP 8.4
- PostgreSQL (or MySQL)
- Redis
- Node.js (for Gemini Live relay `server.js`)

### Setup

```bash
composer install
cp .env.example .env
# Edit .env with database credentials
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### Full Dev Stack

```bash
composer dev
```

Runs in parallel:
- `php artisan serve` (port 8000)
- `php artisan queue:listen`
- `php artisan pail --timeout=0` (real-time logs)
- `npm run dev` (Vite, port 5173)

---

## Environment Variables (Key)

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Application base URL |
| `DB_CONNECTION` | pgsql / mysql / sqlite |
| `REDIS_HOST` | Queue/cache driver |
| `TWILIO_SID` | Twilio account SID |
| `TWILIO_TOKEN` | Twilio auth token |
| `TWILIO_VERIFY_SID` | `VA5f1c126dd6b47bcd05492197c1c36f73` |
| `SUMIT_*` | SUMIT payment gateway credentials |
| `GEMINI_API_KEY` | (Node.js) Gemini Live |
| `PHP_WEBHOOK` | (Node.js) Laravel RSVP endpoint |
| `PRODUCT_ENGINE_FEATURE_CACHE_TTL` | Feature cache TTL (default: 300s) |
| `PRODUCT_ENGINE_CACHE_STORE` | Cache store for product engine |
| `PRODUCT_ENGINE_USAGE_POLICY` | `hard` (default) or `soft` |

---

## Queue

- Driver: Redis
- All time-consuming operations should use `ShouldQueue`
- `SyncOrganizationSubscriptionsJob` — syncs subscription state for an org
- Start: `php artisan queue:listen`
- Restart after deploy: `php artisan queue:restart`

---

## Scheduled Tasks (Product Engine)

| Task | Frequency |
|------|-----------|
| `ProcessTrialExpirationsCommand` | Every 5 minutes |
| `CheckIntegrityCommand` | Hourly |
| `ProductEngineHealthCommand` | On demand |

Configured in `config/product-engine.php`:
```
PRODUCT_ENGINE_TRIAL_EXPIRATIONS_FREQUENCY=everyFiveMinutes
PRODUCT_ENGINE_INTEGRITY_CHECKS_FREQUENCY=hourly
```

---

## Monitoring

### Laravel Pulse
- Dashboard: `/pulse`
- Tracks: requests, queues, slow queries, exceptions, Redis

### Laravel Telescope
- Dashboard: `/telescope`
- Tracks: requests, queries, mail, notifications, jobs
- Restricted to system admins

### Pail (Log Viewer)
```bash
php artisan pail --timeout=0
```

---

## Code Quality

```bash
vendor/bin/pint --dirty    # Format changed PHP files
php artisan test --compact # Run full test suite
php artisan test --filter=ClassName  # Run specific test
```

**Standards**:
- `declare(strict_types=1)` in all PHP files
- Laravel Pint formatter (enforced before committing)
- PHPUnit tests (no Pest — convert if found)
- Form Request classes for all validation (never inline)
- No `env()` calls outside config files

---

## Cache Management

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan queue:restart
```

---

## Database

```bash
php artisan migrate:fresh --seed   # Reset + seed (dev only)
php artisan migrate:rollback       # Rollback last batch
php artisan migrate:status         # Check migration status
```

---

## Node.js Voice Relay (`server.js`)

Separate Node.js process for Gemini Live voice relay.

- Listens for Twilio Media Stream WebSocket connections
- Relays bidirectionally to Gemini `BidiGenerateContent`
- Receives guest/event/invitation params on connection
- Calls `save_rsvp` tool → POSTs to `PHP_WEBHOOK` (Laravel)

Must be running alongside Laravel for voice RSVP to function.

---

## Git

- Main branch: `main` (at `origin/main`)
- Active feature branch: `feature/4-business-areas`
- `declare(strict_types=1)` enforced in all PHP

---

## Related

- [[Architecture/Overview|System Overview]]
- [[Architecture/Services/Notifications|Notifications & Voice RSVP]]
- `CLAUDE.md` — full development command reference
