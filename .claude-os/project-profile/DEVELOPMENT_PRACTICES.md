# Development Practices — Kalfa RSVP

## Development workflow

- **Setup:** `composer install`, `cp .env.example .env`, `php artisan key:generate`, configure DB in `.env`, `php artisan migrate`, `npm install`, `npm run build`.
- **Full stack dev:** `composer dev` runs in parallel: `php artisan serve`, `php artisan queue:listen`, `php artisan pail`, `npm run dev` (Vite on 5173).
- **Single services:** `php artisan serve`, `php artisan queue:listen`, `php artisan pail --timeout=0`, `npm run dev` as needed.
- **Database:** `php artisan migrate:fresh --seed`, `php artisan migrate:rollback`, `php artisan migrate:status`.
- **Caches:** `php artisan config:clear`, `php artisan cache:clear`, `php artisan route:clear`, `php artisan view:clear`, `php artisan queue:restart` when config or code changes.

## Testing

- **Runner:** PHPUnit 11; `php artisan test`; `php artisan test --filter=ClassName`; `php artisan test --stop-on-failure`.
- **Config:** `phpunit.xml`; tests under `tests/Feature/`, `tests/Unit/`; SQLite in-memory for tests (env in phpunit.xml).
- **No coverage config** in phpunit.xml by default; add if needed for CI.

## Git / deployment

- **Branch:** Main branch `main`; feature work on branches; no forced workflow beyond that in docs.
- **Deploy:** Standard Laravel deploy (composer install --no-dev, npm run build, migrate, cache config/route/view, queue workers as needed). Document root to `public/`.
- **Secrets:** `.env` for environment; never commit credentials; `BILLING_WEBHOOK_SECRET` and SUMIT keys in env.

## Code review / quality

- Run `php artisan pint` before committing to keep formatting consistent.
- Policies and scoping: ensure new tenant routes use organization scope and policies.
- Panel UI: new tenant pages follow page pattern (layout + page-header + Livewire or controller view); system pages under `system.*` with Livewire in `App\Livewire\System\`.

## Debugging

- Logs: `storage/logs/laravel.log`; `php artisan pail` for real-time tail.
- Config/cache issues: `php artisan config:clear` and `php artisan cache:clear`.
- Queue: `php artisan queue:listen` in dev; failed jobs in `failed_jobs` table and `php artisan queue:retry` or `queue:flush`.
