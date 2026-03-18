---
date: 2026-03-16
tags: [architecture, database, schema, models]
status: active
---

# Database Schema

**Engine**: PostgreSQL (production) / SQLite (tests)

---

## Core Domain Tables

### `users`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string | |
| `email` | string unique | |
| `password` | string hashed | |
| `current_organization_id` | FK → organizations | DB source of truth for active org |
| `is_system_admin` | boolean | System-wide superuser |
| `is_disabled` | boolean | Blocks login |
| `last_login_at` | timestamp | |
| `email_verified_at` | timestamp | |

---

### `organizations`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `account_id` | FK → accounts | Billing account |
| `name` | string | |
| `slug` | string unique | URL identifier |
| `billing_email` | string | |
| `settings` | json | Org-level settings |
| `is_suspended` | boolean | Admin suspension flag |

---

### `organization_users` (pivot)
| Column | Type | Notes |
|--------|------|-------|
| `user_id` | FK → users | |
| `organization_id` | FK → organizations | |
| `role` | enum | Owner, Admin, Editor, Viewer |

---

### `organization_invitations`
Pending invitations to join an organization by email.

---

### `events`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `organization_id` | FK → organizations | |
| `name` | string | |
| `slug` | string | Public URL identifier |
| `event_date` | date | |
| `status` | enum | Draft, PendingPayment, Active, Completed, Cancelled |

---

### `guests`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `event_id` | FK → events | |
| `name` | string | |
| `phone` | string | E.164 format |
| `email` | string | Optional |

---

### `invitations`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `event_id` | FK → events | |
| `guest_id` | FK → guests | |
| `token` | string(32) | Secure operations |
| `slug` | string(10) | Public RSVP URL: `/rsvp/{slug}` |
| `status` | enum | Pending, Sent, Responded |

---

### `rsvp_responses`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `invitation_id` | FK → invitations | |
| `response_type` | enum | Attending, Declining, Maybe |
| `guest_count` | int | Number attending |

---

### `event_tables`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `event_id` | FK → events | |
| `name` | string | Table label |
| `capacity` | int | Max guests per table |

---

### `seat_assignments`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `event_id` | FK → events | |
| `guest_id` | FK → guests | |
| `event_table_id` | FK → event_tables | |
| `seat_number` | int | Optional |

---

## Billing Tables

### `plans`
Legacy event payment plans (price, name).

### `events_billing`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `organization_id` | FK → organizations | RESTRICT on delete |
| `account_id` | FK → accounts | |
| `event_id` | FK → events | RESTRICT on delete |
| `plan_id` | FK → plans | |
| `amount_cents` | int | In ILS |
| `currency` | string | Default: ILS |
| `status` | enum | Pending, Paid, Failed |

### `payments`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `organization_id` | FK → organizations | RESTRICT on delete |
| `account_id` | FK → accounts | |
| `amount_cents` | int | |
| `currency` | string | |
| `status` | enum | Pending, Processing, Succeeded, Failed |
| `gateway` | string | sumit, stub |
| `gateway_transaction_id` | string | |
| `gateway_response` | json | |

### `billing_webhook_events`
Raw webhook payloads from payment gateway.

### `billing_intents`
Tracks payment intent records per account.

---

## Product Engine Tables

### `accounts`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `owner_user_id` | FK → users | |
| `name` | string | |
| `type` | string | organization / individual |
| `sumit_customer_id` | int | SUMIT CRM customer |
| `twilio_subaccount_sid` | string | Per-account Twilio |

### `products`
| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `name` | string | |
| `sku` | — | removed (was added then dropped) |
| `status` | enum | Active, Inactive, Draft |
| `category` | string | |

### `product_entitlements`
| Column | Type | Notes |
|--------|------|-------|
| `product_id` | FK → products | |
| `feature_key` | string | Feature identifier |
| `value` | string | Stored as string |
| `type` | enum | Boolean, Number, Text |

### `product_features`
Feature metadata (display name, description) for product entitlements.

### `product_limits`
Limit metadata (display name, unit) for plan limits.

### `account_products`
| Column | Type | Notes |
|--------|------|-------|
| `account_id` | FK → accounts | |
| `product_id` | FK → products | |
| `status` | enum | Active, Inactive |
| `granted_at` | timestamp | |
| `expires_at` | timestamp | Null = never |
| `granted_by` | FK → users | Set = manual admin grant |

### `account_entitlements`
| Column | Type | Notes |
|--------|------|-------|
| `account_id` | FK → accounts | |
| `product_entitlement_id` | FK → product_entitlements | Null = manual override |
| `feature_key` | string | |
| `value` | string | |
| `type` | enum | Boolean, Number, Text |
| `expires_at` | timestamp | |

### `product_plans`
| Column | Type | Notes |
|--------|------|-------|
| `product_id` | FK → products | |
| `name` | string | |
| `sku` | string unique | Plan identifier |
| `metadata` | json | Contains `limits` map |
| `sort_order` | int | Display ordering |

### `product_prices`
| Column | Type | Notes |
|--------|------|-------|
| `product_plan_id` | FK → product_plans | |
| `amount_cents` | int | |
| `billing_cycle` | enum | monthly, yearly, one_time |
| `currency` | string | |

### `account_subscriptions`
| Column | Type | Notes |
|--------|------|-------|
| `account_id` | FK → accounts | |
| `product_plan_id` | FK → product_plans | |
| `status` | enum | Active, Cancelled, Expired, Trial |
| `started_at` | timestamp | |
| `ends_at` | timestamp | |
| `trial_ends_at` | timestamp | |

### `usage_records`
Per-subscription usage tracking for metered features.

### `account_feature_usage`
Aggregate per-account usage counters.

---

## System Tables

### `system_audit_logs`
All system admin actions (impersonation, account management, etc.)

### `webauthn_credentials`
Passkey credentials per user (Laragear WebAuthn).

### `personal_access_tokens`
Laravel Sanctum tokens.

### `notifications`
Laravel database notifications channel.

---

## OfficeGuy / SUMIT Tables (Vendor)

| Table | Purpose |
|-------|---------|
| `officeguy_transactions` | SUMIT transaction records |
| `officeguy_tokens` | SUMIT payment tokens |
| `officeguy_documents` | SUMIT invoices/receipts |
| `officeguy_crm_*` | SUMIT CRM entities, folders, activities |

---

## Entity Relationships (Key)

```
User ──m:m──► Organization (via organization_users, role)
Organization ──1:1──► Account
Account ──1:m──► AccountProduct ──m:1──► Product
Account ──1:m──► AccountSubscription ──m:1──► ProductPlan
Account ──1:m──► AccountEntitlement
Organization ──1:m──► Event ──1:m──► Guest
                              Event ──1:m──► EventTable
                              Event ──1:m──► Invitation ──1:1──► RsvpResponse
                              Guest ──1:1──► SeatAssignment ──m:1──► EventTable
Organization ──1:m──► Payment
Organization ──1:m──► EventBilling ──1:1──► Event
```

---

## Related

- [[Architecture/Overview|System Overview]]
- [[Architecture/Services/FeatureResolver|Feature Resolver / Product Engine]]
- [[Architecture/Services/BillingService|BillingService]]
- `database/migrations/` — full migration history
