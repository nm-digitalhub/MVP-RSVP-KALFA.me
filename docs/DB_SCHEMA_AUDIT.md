# DB Schema Audit — Account + Entitlements Phase

Evidence-based audit of all existing tables and columns relevant to organizations, users, events, billing, payments, pivots, and billing/subscription/limits/usage. Source: migration files and package references.

---

## 1. Host tables (application migrations)

### 1.1 `users`
| Column | Type | Constraints | Evidence |
|--------|------|-------------|----------|
| id | bigint PK | | `0001_01_01_000000_create_users_table.php:14` |
| name | string | | ibid |
| email | string | unique | ibid |
| email_verified_at | timestamp nullable | | ibid |
| password | string | | ibid |
| remember_token | string | | ibid |
| timestamps | | | ibid |
| current_organization_id | FK nullable → organizations.id | nullOnDelete | `2026_03_03_100000_add_current_organization_id_to_users_table.php:12` |
| last_login_at | timestamp nullable | | `2026_03_04_120000_add_last_login_at_and_is_disabled_to_users_table.php:14` |
| is_system_admin | boolean | default false, index | `2026_03_02_224239_add_is_system_admin_to_users_table.php:15` |
| is_disabled | boolean | default false, index | `2026_03_04_120000_add_last_login_at_and_is_disabled_to_users_table.php:15` |

No `sumit_customer_id`, no `account_id`. No billing/subscription/limits columns.

---

### 1.2 `organizations`
| Column | Type | Constraints | Evidence |
|--------|------|-------------|----------|
| id | bigint PK | | `2026_03_01_100000_create_organizations_table.php:14` |
| name | string | | ibid:15 |
| slug | string | unique | ibid:16 |
| billing_email | string nullable | | ibid:17 |
| settings | json nullable | | ibid:18 |
| timestamps | | | ibid:19 |
| is_suspended | boolean | default false, index | `2026_03_04_110000_add_is_suspended_to_organizations_table.php:14` |

No `sumit_customer_id`, no `account_id`. No subscription/limits/usage columns.

---

### 1.3 `organization_users` (pivot)
| Column | Type | Constraints | Evidence |
|--------|------|-------------|----------|
| id | bigint PK | | `2026_03_01_100001_create_organization_users_table.php:14` |
| organization_id | FK → organizations.id | cascadeOnDelete | ibid:15 |
| user_id | FK → users.id | cascadeOnDelete | ibid:16 |
| role | string(20) | | ibid:17 |
| timestamps | | | ibid:18 |
| unique(organization_id, user_id) | | | ibid:20 |

---

### 1.4 `events`
| Column | Type | Constraints | Evidence |
|--------|------|-------------|----------|
| id | bigint PK | | `2026_03_01_100002_create_events_table.php:14` |
| organization_id | FK → organizations.id | cascadeOnDelete | ibid:15 |
| name | string | | ibid:16 |
| slug | string | | ibid:17 |
| event_date | date nullable | | ibid:18 |
| venue_name | string nullable | | ibid:19 |
| settings | json nullable | | ibid:20 |
| status | string(30) | | ibid:21 |
| timestamps | | | ibid:22 |
| soft deletes | | | ibid:23 |
| unique(organization_id, slug) | | | ibid:25 |
| index(organization_id, status) | | | ibid:26 |
| index(organization_id, event_date) | | | ibid:27 |

No `account_id`. No limits/usage columns.

---

### 1.5 `events_billing`
| Column | Type | Constraints | Evidence |
|--------|------|-------------|----------|
| id | bigint PK | | `2026_03_01_100009_create_events_billing_table.php:14` |
| organization_id | FK → organizations.id | cascadeOnDelete (later restrictOnDelete not applied to org) | ibid:15 |
| event_id | FK → events.id | cascadeOnDelete; later **restrictOnDelete** | ibid:16; `2026_03_01_120000_events_billing_event_id_restrict_on_delete.php:19` |
| plan_id | FK nullable → plans.id | nullOnDelete | ibid:17 |
| amount_cents | unsignedInteger | default 0 | ibid:18 |
| currency | string(3) | default 'ILS' | ibid:19 |
| status | string(20) | | ibid:20 |
| paid_at | timestamp nullable | | ibid:21 |
| timestamps | | | ibid:22 |
| index(organization_id), index(event_id) | | | ibid:24-25 |

No `account_id`. No subscription/limits columns.

---

### 1.6 `payments`
| Column | Type | Constraints | Evidence |
|--------|------|-------------|----------|
| id | bigint PK | | `2026_03_01_100010_create_payments_table.php:14` |
| organization_id | FK → organizations.id | cascadeOnDelete; later **restrictOnDelete** | ibid:15; `2026_03_01_130000_payments_organization_id_restrict_on_delete.php:19` |
| payable_type | string | morph | ibid:16 |
| payable_id | unsignedBigInteger | morph | ibid:17 |
| amount_cents | unsignedInteger | default 0 | ibid:18 |
| currency | string(3) | default 'ILS' | ibid:19 |
| status | string(20) | | ibid:20 |
| gateway | string(50) nullable | | ibid:21 |
| gateway_transaction_id | string nullable | unique | ibid:22 |
| gateway_response | json nullable | | ibid:23 |
| timestamps | | | ibid:24 |
| index(organization_id, status) | | | ibid:27 |
| index(payable_type, payable_id) | | | ibid:28 |

No `account_id`. Polymorphic payable → EventBilling in practice.

---

### 1.7 `plans`
| Column | Type | Constraints | Evidence |
|--------|------|-------------|----------|
| id | bigint PK | | `2026_03_01_100008_create_plans_table.php:14` |
| name | string | | ibid:15 |
| slug | string | unique | ibid:16 |
| type | string(20) | | ibid:17 |
| limits | json nullable | | ibid:18 |
| price_cents | unsignedInteger | default 0 | ibid:19 |
| billing_interval | string(20) nullable | | ibid:20 |
| timestamps | | | ibid:21 |

`limits` exists but is not used for enforcement in app code (see PHASE_KALFA_C audit). No subscription_id.

---

### 1.8 `billing_webhook_events`
| Column | Type | Constraints | Evidence |
|--------|------|-------------|----------|
| id | bigint PK | | `2026_03_01_100011_create_billing_webhook_events_table.php:14` |
| source | string(50) | index | ibid:15 |
| event_type | string(100) nullable | | ibid:16 |
| payload | json nullable | | ibid:17 |
| processed_at | timestamp nullable | | ibid:18 |
| timestamps | | | ibid:19 |
| index(source, created_at) | | | ibid:21 |

No FK to organizations/payments. Audit only.

---

### 1.9 Other host tables (reference only)
- **guests**: event_id → events; no billing/limits. (`2026_03_01_100003_create_guests_table.php`)
- **invitations**: event_id, guest_id; no billing. (`2026_03_01_100004_create_invitations_table.php`)
- **rsvp_responses**: invitation_id, guest_id; no billing. (`2026_03_01_100005_create_rsvp_responses_table.php`)
- **event_tables**: event_id; no billing. (`2026_03_01_100006_create_tables_table.php` → `event_tables`)
- **seat_assignments**: event_id, guest_id, event_table_id; no billing. (`2026_03_01_100007_create_seat_assignments_table.php`)
- **system_audit_logs**: actor_id → users; target polymorphic; no billing. (`2026_03_04_100000_create_system_audit_logs_table.php`)
- **personal_access_tokens**: tokenable morph; no billing. (`2026_03_01_092523_create_personal_access_tokens_table.php`)
- **cache**, **cache_locks**, **jobs**, **job_batches**, **failed_jobs**, **sessions**, **password_reset_tokens**: framework; not billing-related.

---

## 2. Vendor/package tables (referenced or created by officeguy/laravel-sumit-gateway)

Evidence: host migrations reference these; package creates them.

| Table | Purpose | Key columns (evidence) |
|-------|---------|------------------------|
| officeguy_transactions | Payment transactions | id, order_id, order_type, customer_id (SUMIT), payment_id, amount, status (`vendor/.../2024_01_01_000001_create_officeguy_transactions_table.php:21-45`) |
| officeguy_tokens | Payment tokens | (package migration `2024_01_01_000002_create_officeguy_tokens_table.php`) |
| officeguy_documents | Documents | (package migration `2024_01_01_000003_create_officeguy_documents_table.php`) |
| officeguy_subscriptions | Recurring subscriptions | morphs('subscriber'); recurring_id, status (`vendor/.../2025_01_01_000006_create_subscriptions_table.php:20-41`) |
| officeguy_webhook_events | Webhook delivery | order_type, order_id; transaction_id, document_id, token_id, subscription_id; customer_email, customer_id (`2025_01_01_000008_create_webhook_events_table.php:23-49`) |
| officeguy_sumit_webhooks | SUMIT webhook payloads | customer_id, customer_email; transaction_id, document_id, token_id, subscription_id (`2025_01_01_000009_create_sumit_incoming_webhooks_table.php:23-57`) |

Host migration `2025_12_26_000001_add_transaction_linking_fields_to_officeguy_transactions.php` adds `refund_transaction_id` and indexes to `officeguy_transactions`; FKs in webhook migrations point to `officeguy_subscriptions` (not host tables).

---

## 3. Billing / subscription / limits / usage — summary

| Location | Finding | Evidence |
|----------|---------|----------|
| organizations | No sumit_customer_id, no account_id, no limits/usage columns | §1.2 |
| users | No sumit_customer_id, no account_id | §1.1 |
| events_billing | organization_id, event_id, plan_id; no account_id | §1.5 |
| payments | organization_id, payable morph; no account_id | §1.6 |
| plans | `limits` json present; not used for gating in app | §1.7; PHASE_KALFA_C_PACKAGE_FEATURE_CAPABILITY_AUDIT.md |
| Host | No tables named subscription, package, feature, addon, quota, credits, usage | Grep/migrations |
| Vendor | officeguy_subscriptions = package subscription (morph subscriber); no host “account” table | §2 |

---

## 4. Foreign key graph (host, billing-relevant)

```
users.current_organization_id → organizations.id (nullOnDelete)
organization_users.organization_id → organizations.id (cascadeOnDelete)
organization_users.user_id → users.id (cascadeOnDelete)
events.organization_id → organizations.id (cascadeOnDelete)
events_billing.organization_id → organizations.id (cascadeOnDelete)
events_billing.event_id → events.id (restrictOnDelete)
events_billing.plan_id → plans.id (nullOnDelete)
payments.organization_id → organizations.id (restrictOnDelete)
payments.(payable_type, payable_id) → events_billing (morph)
```

Conclusion: **Billable subject in DB is Organization** (events_billing.organization_id, payments.organization_id). No Account table; no sumit_customer_id on Organization. Additive insertion point: add nullable `account_id` to organizations (and optionally events_billing, payments) without changing existing FKs or constraints.
