# Database Schema & Configuration Report

> **Generated:** 2026-03-18  
> **Project:** Kalfa RSVP + Seating SaaS  
> **Framework:** Laravel (PHP)  
> **Database:** PostgreSQL (`kalfa_rsvp`)  
> **Total Migrations:** 97 files  
> **Total Routes:** 193

---

## Table of Contents

1. [Database Schema](#1-database-schema)
   - [Core Laravel Tables](#11-core-laravel-tables)
   - [Authentication & Authorization](#12-authentication--authorization)
   - [Multi-Tenancy (Organizations & Accounts)](#13-multi-tenancy-organizations--accounts)
   - [Events & RSVP Domain](#14-events--rsvp-domain)
   - [Billing & Payments](#15-billing--payments)
   - [Product Engine & Entitlements](#16-product-engine--entitlements)
   - [Subscriptions & Usage](#17-subscriptions--usage)
   - [Coupons & Credits](#18-coupons--credits)
   - [SUMIT/OfficeGuy Payment Gateway](#19-sumitoffice-guy-payment-gateway)
   - [CRM (SUMIT-backed)](#110-crm-sumit-backed)
   - [Webhooks & Integration](#111-webhooks--integration)
   - [Monitoring & Observability](#112-monitoring--observability)
   - [Media & Misc](#113-media--misc)
2. [Migration Timeline](#2-migration-timeline)
3. [Foreign Key Relationships Map](#3-foreign-key-relationships-map)
4. [Seeder Inventory](#4-seeder-inventory)
5. [Factory Inventory](#5-factory-inventory)
6. [Settings System (Spatie)](#6-settings-system-spatie)
7. [Configuration Map](#7-configuration-map)
8. [Required Environment Variables](#8-required-environment-variables)
9. [Route Summary](#9-route-summary)

---

## 1. Database Schema

### 1.1 Core Laravel Tables

#### `users`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK, auto-increment |
| name | string | |
| email | string | unique |
| email_verified_at | timestamp | nullable |
| password | string | |
| remember_token | string | nullable |
| is_system_admin | boolean | default false, indexed |
| current_organization_id | foreignId → organizations | nullable, nullOnDelete |
| last_login_at | timestamp | nullable |
| is_disabled | boolean | default false, indexed |
| created_at / updated_at | timestamps | |

#### `password_reset_tokens`
| Column | Type | Constraints |
|--------|------|-------------|
| email | string | PK |
| token | string | |
| created_at | timestamp | nullable |

#### `sessions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | string | PK |
| user_id | foreignId → users | nullable, indexed |
| ip_address | string(45) | nullable |
| user_agent | text | nullable |
| payload | longText | |
| last_activity | integer | indexed |

#### `cache`
| Column | Type | Constraints |
|--------|------|-------------|
| key | string | PK |
| value | mediumText | |
| expiration | integer | |

#### `cache_locks`
| Column | Type | Constraints |
|--------|------|-------------|
| key | string | PK |
| owner | string | |
| expiration | integer | |

#### `jobs`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| queue | string | indexed |
| payload | longText | |
| attempts | tinyint unsigned | |
| reserved_at | unsigned int | nullable |
| available_at | unsigned int | |
| created_at | unsigned int | |

#### `job_batches`
| Column | Type | Constraints |
|--------|------|-------------|
| id | string | PK |
| name | string | |
| total_jobs | integer | |
| pending_jobs | integer | |
| failed_jobs | integer | |
| failed_job_ids | longText | |
| options | mediumText | nullable |
| cancelled_at | integer | nullable |
| created_at | integer | |
| finished_at | integer | nullable |

#### `failed_jobs`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| uuid | string | unique |
| connection | text | |
| queue | text | |
| payload | longText | |
| exception | longText | |
| failed_at | timestamp | default current |

#### `notifications`
| Column | Type | Constraints |
|--------|------|-------------|
| id | uuid | PK |
| type | string | |
| notifiable_type / notifiable_id | morphs | indexed |
| data | text | |
| read_at | timestamp | nullable |
| created_at / updated_at | timestamps | |

---

### 1.2 Authentication & Authorization

#### `personal_access_tokens` (Laravel Sanctum)
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| tokenable_type / tokenable_id | morphs | |
| name | text | |
| token | string(64) | unique |
| abilities | text | nullable |
| last_used_at | timestamp | nullable |
| expires_at | timestamp | nullable, indexed |
| created_at / updated_at | timestamps | |

#### `permissions` (Spatie Permission)
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| name | string | unique with guard_name |
| guard_name | string | |
| created_at / updated_at | timestamps | |

#### `roles` (Spatie Permission — team-aware)
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| team_id | bigint unsigned | nullable, indexed |
| name | string | unique with [team_id, guard_name] |
| guard_name | string | |
| created_at / updated_at | timestamps | |

#### `model_has_permissions`
| Column | Type | Constraints |
|--------|------|-------------|
| permission_id | bigint | FK → permissions |
| model_type / model_morph_key | morphs | |
| team_id (if teams) | bigint | nullable |

#### `model_has_roles`
| Column | Type | Constraints |
|--------|------|-------------|
| role_id | bigint | FK → roles |
| model_type / model_morph_key | morphs | |
| team_id (if teams) | bigint | nullable |

#### `role_has_permissions`
| Column | Type | Constraints |
|--------|------|-------------|
| permission_id | bigint | FK → permissions, cascadeOnDelete |
| role_id | bigint | FK → roles, cascadeOnDelete |

#### `webauthn_credentials` (Laragear WebAuthn)
Created via WebAuthn package migration — stores FIDO2/WebAuthn credential data.

---

### 1.3 Multi-Tenancy (Organizations & Accounts)

#### `organizations`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| account_id | foreignId → accounts | nullable, nullOnDelete |
| name | string | |
| slug | string | unique |
| billing_email | string | nullable |
| settings | json | nullable |
| is_suspended | boolean | default false, indexed |
| created_at / updated_at | timestamps | |

#### `organization_users` (pivot)
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| organization_id | foreignId → organizations | cascadeOnDelete |
| user_id | foreignId → users | cascadeOnDelete |
| role | string(20) | owner / admin / member |
| created_at / updated_at | timestamps | |
| **Unique:** | `[organization_id, user_id]` | |

#### `organization_invitations`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| organization_id | foreignId → organizations | cascadeOnDelete |
| email | string | unique with org_id |
| role | string(20) | admin / member |
| token | string(64) | unique |
| expires_at | timestamp | |
| created_at / updated_at | timestamps | |

#### `accounts`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| type | string(30) | indexed (organization / individual) |
| name | string | nullable |
| owner_user_id | foreignId → users | nullable, nullOnDelete |
| sumit_customer_id | bigint unsigned | nullable, indexed |
| credit_balance_agorot | integer | default 0, CHECK ≥ 0 (PG only) |
| twilio_subaccount_sid | string | nullable |
| created_at / updated_at | timestamps | |

---

### 1.4 Events & RSVP Domain

#### `events`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| organization_id | foreignId → organizations | cascadeOnDelete |
| name | string | |
| slug | string | unique with org_id |
| event_date | date | nullable |
| venue_name | string | nullable |
| settings | json | nullable |
| status | string(30) | draft/pending_payment/active/locked/archived/cancelled |
| created_at / updated_at | timestamps | |
| deleted_at | timestamp | soft deletes |
| **Indexes:** | `[org_id, status]`, `[org_id, event_date]` | |

#### `guests`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| event_id | foreignId → events | cascadeOnDelete |
| name | string | |
| email | string | nullable |
| phone | string | nullable |
| group_name | string | nullable |
| notes | text | nullable |
| sort_order | unsigned int | default 0 |
| created_at / updated_at | timestamps | |
| deleted_at | timestamp | soft deletes |

#### `invitations`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| event_id | foreignId → events | cascadeOnDelete |
| guest_id | foreignId → guests | nullable, nullOnDelete |
| token | string | unique |
| slug | string | unique |
| expires_at | timestamp | nullable |
| status | string(20) | pending/sent/opened/responded/expired |
| responded_at | timestamp | nullable |
| created_at / updated_at | timestamps | |

#### `rsvp_responses`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| invitation_id | foreignId → invitations | cascadeOnDelete |
| guest_id | foreignId → guests | nullable, nullOnDelete |
| response | string(10) | yes/no/maybe |
| attendees_count | smallint unsigned | nullable |
| message | text | nullable |
| ip | string(45) | nullable |
| user_agent | text | nullable |
| created_at / updated_at | timestamps | |

#### `event_tables`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| event_id | foreignId → events | cascadeOnDelete |
| name | string | |
| capacity | smallint unsigned | default 0 |
| sort_order | unsigned int | default 0 |
| created_at / updated_at | timestamps | |
| deleted_at | timestamp | soft deletes |

#### `seat_assignments`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| event_id | foreignId → events | cascadeOnDelete |
| guest_id | foreignId → guests | cascadeOnDelete |
| event_table_id | foreignId → event_tables | cascadeOnDelete |
| seat_number | string | nullable |
| created_at / updated_at | timestamps | |
| **Unique:** | `[event_id, guest_id]` | |

---

### 1.5 Billing & Payments

#### `plans`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| product_id | foreignId → products | nullable, nullOnDelete |
| name | string | |
| slug | string | unique |
| type | string(20) | per_event (MVP) |
| limits | json | nullable |
| price_cents | unsigned int | default 0 |
| billing_interval | string(20) | nullable |
| created_at / updated_at | timestamps | |

#### `events_billing`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| organization_id | foreignId → organizations | cascadeOnDelete |
| account_id | foreignId → accounts | nullable, nullOnDelete |
| event_id | foreignId → events | **restrictOnDelete** |
| plan_id | foreignId → plans | nullable, nullOnDelete |
| amount_cents | unsigned int | default 0 |
| currency | string(3) | default 'ILS' |
| status | string(20) | pending/paid/cancelled |
| paid_at | timestamp | nullable |
| created_at / updated_at | timestamps | |

#### `payments`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| organization_id | foreignId → organizations | **restrictOnDelete** |
| account_id | foreignId → accounts | nullable, nullOnDelete |
| payable_type / payable_id | morphs | polymorphic |
| amount_cents | unsigned int | default 0 |
| currency | string(3) | default 'ILS' |
| status | string(20) | pending/succeeded/failed/refunded/cancelled |
| gateway | string(50) | nullable |
| gateway_transaction_id | string | unique |
| gateway_response | json | nullable |
| created_at / updated_at | timestamps | |

#### `billing_webhook_events`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| source | string(50) | indexed |
| event_type | string(100) | nullable |
| payload | json | nullable |
| processed_at | timestamp | nullable |
| created_at / updated_at | timestamps | |

#### `billing_intents`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| account_id | foreignId → accounts | cascadeOnDelete |
| status | string(30) | draft/pending/completed/cancelled, indexed |
| intent_type | string(50) | nullable, indexed |
| payable_type / payable_id | morphs | nullable |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |

---

### 1.6 Product Engine & Entitlements

#### `products`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| name | string | |
| slug | string | unique |
| status | enum/string | draft/active/archived, indexed |
| category | string | nullable |
| description | string | nullable |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |

#### `product_entitlements`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| product_id | foreignId → products | cascadeOnDelete |
| feature_key | string | indexed |
| label | string | nullable |
| type | enum | boolean/number/text/enum, default 'text' |
| is_active | boolean | default true |
| description | text | nullable |
| value | string | nullable |
| constraints | json | nullable |
| created_at / updated_at | timestamps | |

#### `product_features`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| product_id | foreignId → products | cascadeOnDelete |
| feature_key | string | unique with product_id |
| label | string | |
| value | string | nullable |
| description | text | nullable |
| is_enabled | boolean | default true |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |

#### `product_limits`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| product_id | foreignId → products | cascadeOnDelete |
| limit_key | string | unique with product_id |
| label | string | |
| value | string | |
| description | text | nullable |
| is_active | boolean | default true |
| created_at / updated_at | timestamps | |

#### `product_plans`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| product_id | foreignId → products | cascadeOnDelete |
| name | string | |
| slug | string | unique with product_id |
| sku | string | unique |
| description | string | nullable |
| is_active | boolean | default true, indexed |
| sort_order | unsigned int | default 0, indexed |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |

#### `product_prices`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| product_plan_id | foreignId → product_plans | cascadeOnDelete |
| currency | string(3) | |
| amount | bigint unsigned | |
| billing_cycle | string(30) | monthly/usage/etc, indexed |
| is_active | boolean | default true, indexed |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |

#### `account_entitlements`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| account_id | foreignId → accounts | cascadeOnDelete |
| feature_key | string | indexed |
| value | string | nullable |
| type | string(30) | nullable, indexed |
| product_entitlement_id | foreignId → product_entitlements | nullable, nullOnDelete |
| expires_at | timestamp | nullable |
| created_at / updated_at | timestamps | |

#### `account_products`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| account_id | foreignId → accounts | cascadeOnDelete |
| product_id | foreignId → products | cascadeOnDelete |
| status | string(30) | indexed |
| granted_at | timestamp | nullable, indexed |
| expires_at | timestamp | nullable, indexed |
| granted_by | foreignId → users | nullable, nullOnDelete |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |

#### `account_feature_usage`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| account_id | foreignId → accounts | cascadeOnDelete |
| feature_key | string | indexed |
| period_key | unsigned int | indexed (YYYYMM) |
| usage_count | unsigned int | default 0 |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |
| **Unique:** | `[account_id, feature_key, period_key]` | |

---

### 1.7 Subscriptions & Usage

#### `account_subscriptions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| account_id | foreignId → accounts | cascadeOnDelete |
| product_plan_id | foreignId → product_plans | cascadeOnDelete |
| status | string(30) | trial/active/etc, indexed |
| started_at | timestamp | nullable, indexed |
| trial_ends_at | timestamp | nullable |
| ends_at | timestamp | nullable, indexed |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |

#### `usage_records`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| account_id | foreignId → accounts | cascadeOnDelete |
| product_id | foreignId → products | cascadeOnDelete |
| metric_key | string | indexed |
| quantity | bigint unsigned | default 0 |
| recorded_at | timestamp | indexed |
| metadata | json | nullable |
| created_at | timestamp | auto |

#### `officeguy_subscriptions` (SUMIT recurring)
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| subscriber_type / subscriber_id | morphs | polymorphic |
| name | string | |
| amount | decimal(10,2) | |
| currency | string(3) | default 'ILS' |
| interval_months | unsigned int | default 1 |
| total_cycles | unsigned int | nullable |
| completed_cycles | unsigned int | default 0 |
| recurring_id | string | nullable |
| status | string | pending/active/paused/cancelled/expired/failed |
| payment_method_token | string | nullable |
| trial_ends_at | timestamp | nullable |
| next_charge_at | timestamp | nullable |
| last_charged_at | timestamp | nullable |
| cancelled_at | timestamp | nullable |
| expires_at | timestamp | nullable |
| cancellation_reason | string | nullable |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |
| deleted_at | timestamp | soft deletes |

---

### 1.8 Coupons & Credits

#### `coupons`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| code | string(64) | unique |
| description | string | nullable |
| discount_type | string(32) | percentage/fixed/trial_extension |
| discount_value | unsigned int | |
| discount_duration_months | unsigned tinyint | nullable (null = forever) |
| target_type | string(32) | default 'global' |
| target_ids | json | nullable |
| max_uses | unsigned int | nullable |
| max_uses_per_account | unsigned int | nullable |
| first_time_only | boolean | default false |
| is_active | boolean | default true |
| expires_at | timestamp | nullable |
| created_by | foreignId → users | cascadeOnDelete |
| created_at / updated_at | timestamps | |

#### `coupon_redemptions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| coupon_id | foreignId → coupons | cascadeOnDelete |
| account_id | foreignId → accounts | cascadeOnDelete |
| redeemed_by | foreignId → users | cascadeOnDelete |
| redeemable_type / redeemable_id | morphs | nullable |
| discount_applied | unsigned int | (agorot) |
| trial_days_added | unsigned int | default 0 |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |

#### `account_credit_transactions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| account_id | foreignId → accounts | cascadeOnDelete |
| type | string(10) | 'credit' / 'debit', CHECK constraint (PG) |
| source | string(30) | CreditSource enum, CHECK constraint (PG) |
| amount_agorot | integer | CHECK > 0 (PG) |
| balance_after_agorot | integer | snapshot |
| currency | char(3) | default 'ILS' |
| description | string(255) | nullable |
| reference_type / reference_id | morphs | nullable |
| expiry_at | timestamp | nullable |
| actor_id | foreignId → users | nullable, nullOnDelete |
| created_at | timestamp | auto (append-only) |

**Valid sources:** manual, coupon, refund, checkout_applied, subscription_cycle, adjustment, migration, chargeback, expiry

---

### 1.9 SUMIT/OfficeGuy Payment Gateway

#### `officeguy_transactions`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| order_id | string | indexed |
| order_type | string | nullable (morph type) |
| parent_transaction_id | bigint unsigned | nullable, FK → self, onDelete set null |
| refund_transaction_id | foreignId → self | nullable, onDelete set null |
| vendor_id | string | nullable |
| is_upsell | boolean | default false |
| is_donation | boolean | default false |
| subscription_id | string | nullable |
| payment_id | string | nullable, indexed |
| sumit_entity_id | bigint unsigned | nullable, unique |
| upay_transaction_id | string(50) | nullable, indexed |
| auth_number | string | nullable |
| upay_voucher_number | string(50) | nullable |
| amount | decimal(10,2) | |
| first_payment_amount | decimal(10,2) | nullable |
| non_first_payment_amount | decimal(10,2) | nullable |
| currency | string(3) | |
| payments_count | integer | default 1 |
| status | string | default 'pending' |
| is_webhook_confirmed | boolean | default false, indexed |
| webhook_confirmed_at | timestamp | nullable |
| payment_method | string | default 'card' |
| last_digits | string(4) | nullable |
| expiration_month | string(2) | nullable |
| expiration_year | string(4) | nullable |
| card_type | string | nullable |
| status_description | text | nullable |
| error_message | text | nullable |
| raw_request | json | nullable |
| raw_response | json | nullable |
| environment | string | default 'www' |
| is_test | boolean | default false |
| created_at / updated_at | timestamps | |
| deleted_at | timestamp | soft deletes |

#### `officeguy_tokens`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| owner_type / owner_id | morphs | polymorphic |
| token | string | unique |
| gateway_id | string | default 'officeguy' |
| card_type | string | default 'card' |
| last_four | string(4) | |
| citizen_id | string | nullable |
| expiry_month | string(2) | |
| expiry_year | string(4) | |
| is_default | boolean | default false |
| metadata | json | nullable |
| admin_notes | text | nullable |
| created_at / updated_at | timestamps | |
| deleted_at | timestamp | soft deletes |

#### `officeguy_documents`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| document_id | string | unique |
| document_number | bigint | nullable |
| document_date | timestamp | nullable |
| order_id | string | nullable, indexed |
| order_type | string | nullable |
| subscription_id | bigint unsigned | nullable, FK → officeguy_subscriptions |
| customer_id | string | nullable |
| document_type | string | default '1' |
| is_draft | boolean | default false |
| is_closed | boolean | default false |
| language | string | nullable |
| currency | string(3) | |
| amount | decimal(10,2) | |
| description | text | nullable |
| external_reference | string | nullable, indexed |
| document_download_url | string(500) | nullable |
| document_payment_url | string(500) | nullable |
| emailed | boolean | default false |
| raw_response | json | nullable |
| items | json | nullable |
| created_at / updated_at | timestamps | |
| deleted_at | timestamp | soft deletes |

#### `officeguy_settings`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| key | string | unique, indexed |
| value | json | nullable |
| created_at / updated_at | timestamps | |

#### `officeguy_vendor_credentials`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| vendor_type / vendor_id | morphs | polymorphic |
| company_id | string | |
| api_key | string | |
| public_key | string | nullable |
| merchant_number | string | nullable |
| is_active | boolean | default true |
| validation_status | string | nullable |
| validation_message | text | nullable |
| validated_at | timestamp | nullable |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |
| deleted_at | timestamp | soft deletes |

#### `officeguy_debt_attempts`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| crm_entity_id | bigint unsigned | nullable, indexed |
| sumit_customer_id | bigint unsigned | indexed |
| attempts | unsigned int | default 0 |
| last_sent_at | timestamp | nullable |
| created_at / updated_at | timestamps | |

#### `document_subscription` (pivot)
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| document_id | foreignId → officeguy_documents | cascadeOnDelete |
| subscription_id | foreignId → officeguy_subscriptions | cascadeOnDelete |
| amount | decimal(10,2) | |
| item_data | json | nullable |
| created_at / updated_at | timestamps | |
| **Unique:** | `[document_id, subscription_id]` | |

---

### 1.10 CRM (SUMIT-backed)

#### `officeguy_crm_folders`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| sumit_folder_id | bigint unsigned | unique, nullable |
| name | string | |
| name_plural | string | |
| icon | string(100) | nullable |
| color | string(7) | nullable |
| entity_type | string(100) | contact/lead/company/deal, indexed |
| is_system | boolean | default false |
| is_active | boolean | default true, indexed |
| settings | json | nullable |
| created_at / updated_at | timestamps | |

#### `officeguy_crm_folder_fields`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| crm_folder_id | foreignId → crm_folders | cascadeOnDelete |
| sumit_field_id | bigint unsigned | nullable |
| name | string | unique with crm_folder_id |
| label | string | |
| field_type | string(50) | text/number/email/phone/date/select/multiselect/boolean |
| is_required | boolean | default false |
| is_unique | boolean | default false |
| is_searchable | boolean | default true |
| default_value | text | nullable |
| validation_rules | json | nullable |
| options | json | nullable |
| display_order | integer | default 0 |
| created_at / updated_at | timestamps | |

#### `officeguy_crm_entities`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| crm_folder_id | foreignId → crm_folders | cascadeOnDelete |
| sumit_entity_id | bigint unsigned | unique, nullable |
| entity_type | string(100) | indexed |
| name | string | fulltext (PG) |
| email | string | nullable, indexed |
| phone | string(50) | nullable, indexed |
| mobile | string(50) | nullable |
| address | text | nullable |
| city | string(100) | nullable |
| state | string(100) | nullable |
| postal_code | string(20) | nullable |
| country | string(100) | default 'Israel' |
| company_name | string | nullable, fulltext (PG) |
| tax_id | string(50) | nullable |
| status | string(50) | default 'active', indexed |
| source | string(100) | nullable |
| owner_user_id | bigint unsigned | nullable, indexed |
| assigned_to_user_id | bigint unsigned | nullable |
| client_id | bigint unsigned | nullable, indexed |
| sumit_customer_id | bigint unsigned | nullable, indexed |
| last_contact_at | timestamp | nullable |
| created_at / updated_at | timestamps | |
| deleted_at | timestamp | soft deletes |

#### `officeguy_crm_entity_fields`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| crm_entity_id | foreignId → crm_entities | cascadeOnDelete |
| crm_folder_field_id | foreignId → crm_folder_fields | cascadeOnDelete |
| value | text | nullable |
| value_numeric | decimal(15,2) | nullable, indexed |
| value_date | date | nullable, indexed |
| value_boolean | boolean | nullable |
| created_at / updated_at | timestamps | |
| **Unique:** | `[crm_entity_id, crm_folder_field_id]` | |

#### `officeguy_crm_entity_relations`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| from_entity_id | foreignId → crm_entities | cascadeOnDelete |
| to_entity_id | foreignId → crm_entities | cascadeOnDelete |
| relation_type | string(100) | parent/child/related/duplicate/merged |
| metadata | json | nullable |
| created_at / updated_at | timestamps | |
| **Unique:** | `[from_entity_id, to_entity_id, relation_type]` | |

#### `officeguy_crm_activities`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| crm_entity_id | foreignId → crm_entities | cascadeOnDelete |
| client_id | bigint unsigned | nullable, indexed |
| user_id | bigint unsigned | nullable, indexed |
| activity_type | string(100) | call/email/meeting/note/task/sms/whatsapp, indexed |
| subject | string | |
| description | text | nullable |
| status | string(50) | planned/in_progress/completed/cancelled, indexed |
| priority | string(50) | default 'normal' |
| start_at | timestamp | nullable, indexed |
| end_at | timestamp | nullable |
| reminder_at | timestamp | nullable |
| related_document_id | foreignId → officeguy_documents | nullable, onDelete set null |
| related_ticket_id | bigint unsigned | nullable |
| created_at / updated_at | timestamps | |

#### `officeguy_crm_views`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| crm_folder_id | foreignId → crm_folders | cascadeOnDelete |
| sumit_view_id | bigint unsigned | nullable |
| name | string | |
| is_default | boolean | default false, indexed |
| is_public | boolean | default false, indexed |
| user_id | bigint unsigned | nullable, indexed |
| filters | json | nullable |
| sort_by | string | nullable |
| sort_direction | string(4) | default 'asc' |
| columns | json | nullable |
| created_at / updated_at | timestamps | |

---

### 1.11 Webhooks & Integration

#### `officeguy_webhook_events`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| event_type | string(50) | indexed |
| status | string(20) | default 'pending', indexed |
| webhook_url | string | nullable |
| http_status_code | integer | nullable |
| payload | json | nullable |
| response | json | nullable |
| error_message | text | nullable |
| retry_count | integer | default 0 |
| next_retry_at | timestamp | nullable |
| sent_at | timestamp | nullable |
| transaction_id | FK → officeguy_transactions | nullable, set null |
| document_id | FK → officeguy_documents | nullable, set null |
| token_id | FK → officeguy_tokens | nullable, set null |
| subscription_id | FK → subscriptions | nullable, set null |
| order_type / order_id | morphs | nullable |
| customer_email | string | nullable, indexed |
| customer_id | string | nullable, indexed |
| amount | decimal(15,2) | nullable |
| currency | string(10) | nullable |
| created_at / updated_at | timestamps | |

#### `officeguy_sumit_webhooks`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| webhook_id | string | unique, nullable |
| event_type | string(50) | indexed |
| card_type | string(50) | nullable, indexed |
| endpoint | string(190) | nullable, indexed |
| source_ip | string | nullable |
| content_type | string | nullable |
| headers | json | nullable |
| payload | json | nullable |
| card_id | string | nullable, indexed |
| customer_id | string | nullable, indexed |
| client_id | bigint unsigned | nullable, indexed |
| customer_email | string | nullable, indexed |
| customer_name | string | nullable |
| amount | decimal(15,2) | nullable |
| currency | string(10) | nullable |
| status | string(20) | default 'received', indexed |
| processing_notes | text | nullable |
| error_message | text | nullable |
| processed_at | timestamp | nullable |
| transaction_id | FK → officeguy_transactions | nullable, set null |
| document_id | FK → officeguy_documents | nullable, set null |
| token_id | FK → officeguy_tokens | nullable, set null |
| subscription_id | FK → subscriptions | nullable, set null |
| created_at / updated_at | timestamps | |

#### `payable_field_mappings`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| model_class | string | unique |
| label | string | nullable |
| field_mappings | json | |
| is_active | boolean | default true, indexed |
| created_at / updated_at | timestamps | |

---

### 1.12 Monitoring & Observability

#### `system_audit_logs`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| actor_id | foreignId → users | nullable, nullOnDelete |
| target_type | string | nullable |
| target_id | bigint unsigned | nullable |
| action | string(100) | |
| metadata | json | nullable |
| ip_address | string(45) | nullable |
| user_agent | text | nullable |
| created_at / updated_at | timestamps | |

#### Pulse tables: `pulse_values`, `pulse_entries`, `pulse_aggregates`
Laravel Pulse performance monitoring tables with type/key/value/timestamp/aggregation.

#### Telescope tables: `telescope_entries`, `telescope_entries_tags`, `telescope_monitoring`
Laravel Telescope debug/monitoring tables.

---

### 1.13 Media & Misc

#### `media` (Spatie Media Library)
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| model_type / model_id | morphs | polymorphic |
| uuid | uuid | nullable, unique |
| collection_name | string | |
| name | string | |
| file_name | string | |
| mime_type | string | nullable |
| disk | string | |
| conversions_disk | string | nullable |
| size | bigint unsigned | |
| manipulations | json | |
| custom_properties | json | |
| generated_conversions | json | |
| responsive_images | json | |
| order_column | unsigned int | nullable, indexed |
| created_at / updated_at | timestamps | |

#### `settings` (Spatie Laravel Settings)
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| group | string | unique with name |
| name | string | |
| locked | boolean | default false |
| payload | json | |
| created_at / updated_at | timestamps | |

#### `order_success_tokens`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| payable_type / payable_id | morphs | |
| token_hash | string(64) | unique |
| nonce | string(64) | unique |
| expires_at | timestamp | indexed |
| consumed_at | timestamp | nullable, indexed |
| consumed_by_ip | string(45) | nullable |
| consumed_by_user_agent | text | nullable |
| created_at / updated_at | timestamps | |

#### `order_success_access_log`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| payable_type / payable_id | morphs | |
| ip_address | string(45) | indexed |
| user_agent | text | nullable |
| referer | string(500) | nullable |
| is_valid | boolean | default false, indexed |
| validation_failures | json | nullable |
| token_hash | string(64) | nullable, indexed |
| nonce | string(64) | nullable |
| signature_valid | boolean | default false |
| accessed_at | timestamp | default current, indexed |
| created_at / updated_at | timestamps | |

#### `pending_checkouts`
| Column | Type | Constraints |
|--------|------|-------------|
| id | bigint | PK |
| payable_type / payable_id | morphs | indexed |
| customer_data | json | |
| payment_preferences | json | |
| service_data | json | nullable |
| session_id | string | nullable, indexed |
| ip_address | ip | nullable |
| user_agent | text | nullable |
| expires_at | timestamp | indexed |
| created_at / updated_at | timestamps | |

---

## 2. Migration Timeline

| # | Date | Migration | Action |
|---|------|-----------|--------|
| 1 | Bootstrap | `create_users_table` | users, password_reset_tokens, sessions |
| 2 | Bootstrap | `create_cache_table` | cache, cache_locks |
| 3 | Bootstrap | `create_jobs_table` | jobs, job_batches, failed_jobs |
| 4 | 2022-12-14 | `create_settings_table` | Spatie settings store |
| 5 | 2024-01-01 | `create_officeguy_transactions_table` | SUMIT transactions |
| 6 | 2024-01-01 | `create_officeguy_tokens_table` | Saved payment tokens |
| 7 | 2024-01-01 | `create_officeguy_documents_table` | SUMIT invoices/docs |
| 8 | 2025-01-01 | `create_officeguy_settings_table` | Gateway settings KV |
| 9 | 2025-01-01 | `create_vendor_credentials_table` | Multi-vendor SUMIT creds |
| 10 | 2025-01-01 | `create_subscriptions_table` | SUMIT recurring subscriptions |
| 11 | 2025-01-01 | `add_donation_and_vendor_fields` | Upsell/donation fields on transactions |
| 12 | 2025-01-01 | `create_webhook_events_table` | Outbound webhook log |
| 13 | 2025-01-01 | `create_sumit_incoming_webhooks_table` | Inbound SUMIT webhooks |
| 14 | 2025-01-27 | `create_payable_field_mappings_table` | Polymorphic payable mappings |
| 15 | 2025-11-30 | `add_subscription_support_to_documents` | Subscription links on documents |
| 16 | 2025-11-30 | `add_items_to_documents_table` | JSON items on documents |
| 17 | 2025-11-30 | `create_document_subscription_pivot` | M2M docs↔subscriptions |
| 18-23 | 2025-12-01 | CRM tables | folders, fields, entities, entity_fields, relations, activities, views |
| 24 | 2025-12-01 | `add_endpoint_to_sumit_webhooks` | Endpoint column |
| 25 | 2025-12-02 | `create_officeguy_debt_attempts_table` | Debt collection tracking |
| 26-28 | 2025-12-02 | client_id additions | CRM entities, activities, webhooks |
| 29 | 2025-12-07 | `add_admin_notes_to_officeguy_tokens` | Admin notes |
| 30-32 | 2025-12-18 | Order success tokens, access log, webhook confirmation | Secure success pages |
| 33 | 2025-12-18 | `create_pending_checkouts_table` | Temporary checkout storage |
| 34 | 2025-12-24 | `add_secure_success_settings` | Settings seed |
| 35-36 | 2025-12-26/29 | Transaction linking fields, SUMIT entity ID | Refund links, entity matching |
| 37 | 2025-12-29 | `add_upay_fields` | Upay processor fields |
| 38 | 2026-03-01 | `create_personal_access_tokens` | Sanctum |
| 39-50 | 2026-03-01 | Core RSVP domain | organizations, org_users, events, guests, invitations, rsvp_responses, tables, seats, plans, billing, payments, billing webhooks |
| 51 | 2026-03-02 | `add_is_system_admin` | System admin flag |
| 52 | 2026-03-03 | `add_current_organization_id` | User org switching |
| 53-59 | 2026-03-03 | Account/product engine | accounts, products, product_entitlements, account_entitlements, feature_usage, billing_intents, account_id links |
| 60 | 2026-03-04 | `drop_client_id_foreign_keys` | Remove FK to host clients table |
| 61 | 2026-03-04 | `create_media_table` | Spatie Media Library |
| 62-64 | 2026-03-04 | Audit, suspension, login tracking | system_audit_logs, org is_suspended, user last_login/is_disabled |
| 65 | 2026-03-05 | `add_name_to_accounts` | Account name |
| 66-67 | 2026-03-08 | Pulse + Telescope | Monitoring tables |
| 68 | 2026-03-08 | `create_permission_tables` | Spatie Permission (RBAC) |
| 69 | 2026-03-08 | `create_organization_invitations` | Org invite system |
| 70-80 | 2026-03-09 | Product engine expansion | plans→product link, Twilio subaccount, product status/features/limits, account_products, product_plans, prices, subscriptions, usage_records |
| 81 | 2026-03-09 | `create_notifications_table` | Laravel notifications |
| 82-85 | 2026-03-10 | SKU management | product_plans SKU, uniqueness, sort_order |
| 86 | 2026-03-13 | `create_webauthn_credentials` | FIDO2/Passkey support |
| 87-89 | 2026-03-16 | Coupons | coupons, coupon_redemptions, discount_duration |
| 90-91 | 2026-03-17 | Credits | credit_balance on accounts, credit_transactions |

---

## 3. Foreign Key Relationships Map

```
users ──< organization_users >── organizations
users ──< personal_access_tokens (morphs)
users ──── current_organization_id → organizations
users ──< system_audit_logs (actor_id)
users ──< coupons (created_by)
users ──< coupon_redemptions (redeemed_by)
users ──< account_credit_transactions (actor_id)

organizations ──< events
organizations ──< events_billing
organizations ──< payments
organizations ──< organization_invitations
organizations ── account_id → accounts

accounts ──< account_entitlements
accounts ──< account_feature_usage
accounts ──< account_products
accounts ──< account_subscriptions
accounts ──< usage_records
accounts ──< billing_intents
accounts ──< account_credit_transactions
accounts ──< coupon_redemptions

events ──< guests
events ──< invitations
events ──< event_tables
events ──< seat_assignments
events ──< events_billing (restrict delete)

guests ──< invitations (nullOnDelete)
guests ──< rsvp_responses (nullOnDelete)
guests ──< seat_assignments (cascadeOnDelete)

event_tables ──< seat_assignments

invitations ──< rsvp_responses

products ──< product_entitlements
products ──< product_features
products ──< product_limits
products ──< product_plans ──< product_prices
products ──< account_products
products ──< usage_records

product_plans ──< account_subscriptions

officeguy_transactions ──< officeguy_transactions (parent/refund self-ref)
officeguy_transactions ──< officeguy_webhook_events
officeguy_transactions ──< officeguy_sumit_webhooks

officeguy_documents ──< officeguy_webhook_events
officeguy_documents ──< officeguy_sumit_webhooks
officeguy_documents ──< document_subscription
officeguy_documents ──< officeguy_crm_activities (related_document_id)

officeguy_subscriptions ──< document_subscription
officeguy_subscriptions ──< officeguy_documents (subscription_id)

officeguy_crm_folders ──< officeguy_crm_folder_fields
officeguy_crm_folders ──< officeguy_crm_entities
officeguy_crm_folders ──< officeguy_crm_views

officeguy_crm_entities ──< officeguy_crm_entity_fields
officeguy_crm_entities ──< officeguy_crm_entity_relations (from/to)
officeguy_crm_entities ──< officeguy_crm_activities

roles ──< model_has_roles
roles ──< role_has_permissions
permissions ──< model_has_permissions
permissions ──< role_has_permissions
```

---

## 4. Seeder Inventory

| Seeder | Purpose |
|--------|---------|
| **DatabaseSeeder** | Master seeder — calls InitialAdmin, PlanSeeder, TwilioSmsProduct, AiVoiceAgentProduct; creates test user |
| **InitialAdminSeeder** | Creates `admin@kalfa.me` with random password when no users exist |
| **PlanSeeder** | Creates `per-event-basic` plan at 99 ILS |
| **RolesAndPermissionsSeeder** | Creates permissions (manage-system, manage-organizations, etc.) and roles (Super Admin, Org Admin, Org Editor) |
| **TwilioSmsProductSeeder** | Creates "Twilio SMS" product with entitlements: twilio_enabled, sms_confirmation_enabled, sms_confirmation_limit (500/mo) |
| **AiVoiceAgentProductSeeder** | Creates "AI Voice Agent RSVP" product with Starter/Growth/Scale plans, monthly + usage pricing |
| **AppointmentWorkflowSeeder** | Creates sample services and providers (requires Appointment domain models — currently commented out) |
| **CheckoutWorkflowSeeder** | Creates sample cart items for test user (requires CartItem model — currently commented out) |

---

## 5. Factory Inventory

| Factory | Model | Key States |
|---------|-------|------------|
| **UserFactory** | User | `unverified()` |
| **OrganizationFactory** | Organization | Auto-creates owner user |
| **AccountFactory** | Account | organization/individual types |
| **EventFactory** | Event | `active()`, `draft()` |
| **CouponFactory** | Coupon | `percentage()`, `fixed()`, `trialExtension()`, `expired()`, `inactive()`, `firstTimeOnly()`, `withMaxUses()` |
| **ProductFeatureFactory** | ProductFeature | Creates parent product |
| **ProductLimitFactory** | ProductLimit | Creates parent product |
| **AppointmentFactory** | Appointment | `scheduled()`, `completed()`, `cancelled()` |
| **ProviderFactory** | Provider | `unavailable()` |
| **ServiceFactory** | Service | Random from 6 predefined services |
| **TestModelFactory** | TestModel | Basic name field |

---

## 6. Settings System (Spatie)

Uses **Spatie Laravel Settings** with the `settings` table (group + name + payload).

### Settings Migrations (database/settings/)

| Migration | Group | Keys |
|-----------|-------|------|
| `create_sumit_settings` | `sumit` | company_id, private_key, public_key, environment, is_active, is_test_mode |
| `create_twilio_settings` | `twilio` | sid, token, number, messaging_service_sid, verify_sid, api_key, api_secret, is_active |
| `create_gemini_settings` | `gemini` | api_key, model (`models/gemini-2.0-flash-exp`), is_active |

### Settings Classes (app/Settings/)

| Class | Group | Properties |
|-------|-------|------------|
| **SumitSettings** | `sumit` | `company_id`, `private_key`, `public_key`, `environment`, `is_active`, `is_test_mode` |
| **TwilioSettings** | `twilio` | `sid`, `token`, `number`, `messaging_service_sid`, `verify_sid`, `api_key`, `api_secret`, `is_active` |
| **GeminiSettings** | `gemini` | `api_key`, `model`, `is_active` |

---

## 7. Configuration Map

| Config File | Purpose | Key Settings |
|-------------|---------|-------------|
| **app.php** | Core application config | name, env, debug, URL, locale (he), timezone |
| **auth.php** | Authentication guards & providers | Default guard: web (session), passwords: users |
| **billing.php** | Payment gateway selection | `BILLING_GATEWAY` (stub/sumit), SUMIT redirect URLs, webhook secret |
| **blade-iconsax.php** | Iconsax icon set config | prefix: 'iconsax' |
| **broadcasting.php** | Real-time events | Reverb, Pusher, Redis, log drivers |
| **cache.php** | Cache store config | Default: database; supports array, file, redis, memcached |
| **database.php** | Database connections | Default: sqlite (overridden by .env to pgsql); supports MySQL, PG, SQLite |
| **events.php** | RSVP & navigation config | Date formats (he/en), navigation providers (Google Maps, Waze, Apple Maps) |
| **filesystems.php** | Storage disks | local, public, S3 |
| **livewire.php** | Livewire component config | Component locations, namespaces, page layout |
| **logging.php** | Log channels | stack → single/daily; Monolog handlers |
| **mail.php** | Email transport | Default: log; supports SMTP, Mailgun, SES, Postmark, Resend |
| **media-library.php** | Spatie Media Library | disk: public, max 10MB, queue conversions |
| **officeguy.php** | SUMIT credentials & PCI mode | company_id, private/public keys, environment, PCI mode (no/redirect/yes) |
| **officeguy-webhooks.php** | Webhook server config | async, queue, retries, timeouts, HMAC signing |
| **permission.php** | Spatie Permission RBAC | Team-aware roles, permission/role models, table names, cache config |
| **product-engine.php** | Product/entitlement engine | Feature cache TTL (300s), usage policy (hard), trial expiration, integrity checks |
| **pulse.php** | Laravel Pulse monitoring | Domain, path, master switch, recorders |
| **pwa.php** | Progressive Web App | Manifest (name, colors, icons), service worker, debug |
| **queue.php** | Queue connections | Default: database; supports sync, Redis, SQS, Beanstalkd |
| **reverb.php** | WebSocket server | Host, port, scaling, Pusher protocol compat |
| **robotstxt.php** | robots.txt rules | Production allows public pages; disallows admin/API/billing routes; blocks AI training |
| **sanctum.php** | API token auth | Stateful domains, token expiration, guard: web |
| **scramble.php** | API documentation | OpenAPI auto-generation, path: api, Kalfa RSVP + Seating API |
| **services.php** | Third-party credentials | Postmark, Resend, SES, Slack, Twilio |
| **session.php** | Session management | Driver: database, lifetime: 120min |
| **telescope.php** | Debug dashboard | Watchers, domain, path, enabled flag |
| **webauthn.php** | FIDO2/Passkey auth | Relying party name/ID, origins, challenge config |

---

## 8. Required Environment Variables

### Core Application
| Variable | Default | Required | Purpose |
|----------|---------|----------|---------|
| `APP_NAME` | Laravel | ✅ | Application name |
| `APP_ENV` | local | ✅ | Environment (local/staging/production) |
| `APP_KEY` | — | ✅ | Encryption key |
| `APP_DEBUG` | true | ✅ | Debug mode |
| `APP_URL` | http://localhost | ✅ | Base URL |
| `APP_LOCALE` | he | | Default locale (Hebrew) |

### Database
| Variable | Default | Required | Purpose |
|----------|---------|----------|---------|
| `DB_CONNECTION` | pgsql | ✅ | Database driver |
| `DB_HOST` | 127.0.0.1 | ✅ | Database host |
| `DB_PORT` | 5432 | ✅ | Database port |
| `DB_DATABASE` | kalfa_rsvp | ✅ | Database name |
| `DB_USERNAME` | — | ✅ | Database user |
| `DB_PASSWORD` | — | ✅ | Database password |

### Session, Cache, Queue
| Variable | Default | Purpose |
|----------|---------|---------|
| `SESSION_DRIVER` | database | Session storage |
| `CACHE_STORE` | database | Cache backend |
| `QUEUE_CONNECTION` | database | Queue backend |
| `BROADCAST_CONNECTION` | log | Broadcasting driver |
| `FILESYSTEM_DISK` | local | Default disk |

### Redis (optional)
| Variable | Default | Purpose |
|----------|---------|---------|
| `REDIS_HOST` | 127.0.0.1 | Redis server |
| `REDIS_PASSWORD` | null | Redis password |
| `REDIS_PORT` | 6379 | Redis port |

### Mail
| Variable | Default | Purpose |
|----------|---------|---------|
| `MAIL_MAILER` | log | Mail driver |
| `MAIL_HOST` | 127.0.0.1 | SMTP host |
| `MAIL_PORT` | 2525 | SMTP port |
| `MAIL_FROM_ADDRESS` | hello@example.com | Sender address |
| `MAIL_FROM_NAME` | ${APP_NAME} | Sender name |

### Billing (SUMIT)
| Variable | Default | Required | Purpose |
|----------|---------|----------|---------|
| `BILLING_GATEWAY` | stub | ✅ | Gateway (stub/sumit) |
| `BILLING_SUMIT_SUCCESS_URL` | — | When sumit | Redirect after payment |
| `BILLING_SUMIT_CANCEL_URL` | — | When sumit | Redirect on cancel |
| `BILLING_WEBHOOK_SECRET` | — | Recommended | HMAC webhook signing |
| `OFFICEGUY_ENVIRONMENT` | www | | SUMIT env (www/dev/test) |
| `OFFICEGUY_COMPANY_ID` | — | When sumit | SUMIT company ID |
| `OFFICEGUY_PRIVATE_KEY` | — | When sumit | SUMIT API key |
| `OFFICEGUY_PUBLIC_KEY` | — | When sumit | SUMIT public key |

### Twilio
| Variable | Default | Required | Purpose |
|----------|---------|----------|---------|
| `TWILIO_ACCOUNT_SID` | — | For voice/SMS | Account SID |
| `TWILIO_AUTH_TOKEN` | — | For voice/SMS | Auth token |
| `TWILIO_NUMBER` | — | For voice | E.164 phone number |
| `TWILIO_WHATSAPP_FROM` | — | For WhatsApp | WhatsApp sender |
| `TWILIO_VERIFY_SID` | — | For OTP | Verify service SID |

### AWS (optional)
| Variable | Purpose |
|----------|---------|
| `AWS_ACCESS_KEY_ID` | S3/SES access key |
| `AWS_SECRET_ACCESS_KEY` | S3/SES secret |
| `AWS_DEFAULT_REGION` | AWS region |
| `AWS_BUCKET` | S3 bucket name |

### Monitoring (optional)
| Variable | Purpose |
|----------|---------|
| `TELESCOPE_ENABLED` | Enable Telescope debug dashboard |
| `PULSE_DOMAIN` | Pulse monitoring subdomain |

### WebAuthn (optional)
| Variable | Purpose |
|----------|---------|
| `WEBAUTHN_NAME` | Relying party name |
| `WEBAUTHN_ID` | Custom domain for WebAuthn |
| `WEBAUTHN_ORIGINS` | Allowed origins |

---

## 9. Route Summary

**Total routes:** 193

### API Routes (`api/`)
| Group | Routes | Description |
|-------|--------|-------------|
| Organizations | 2 | Show/update organization |
| Events | 5 | CRUD + list events per org |
| Guests | 6 | CRUD + list + import guests |
| Event Tables | 5 | CRUD tables per event |
| Seat Assignments | 2 | List + bulk update seats |
| Invitations | 3 | List + create + send invitations |
| RSVP (public) | 2 | Show invitation + submit response |
| Billing/Checkout | 3 | Checkout purchase, coupon validate |
| Payments | 1 | Show payment |
| Webhooks | 2 | GET + POST webhook handlers |
| Twilio | 2 | Calling log, RSVP process |

### Dashboard Routes (`dashboard/`)
| Group | Routes |
|-------|--------|
| Events CRUD | 7 routes (list/create/show/edit/update/delete) |
| Event sub-resources | Guests, invitations, tables, seat-assignments (read-only views) |
| Organization settings | Edit/update |
| Team | 1 route |

### System Admin Routes (`system/`)
| Group | Routes |
|-------|--------|
| Dashboard | 1 |
| Users | 2 (list/show) |
| Organizations | 2 (list/show) |
| Products | 3 (list/create/show) |
| Accounts | 3 + 3 payment method routes |
| Coupons | 3 (list/create/edit) |
| Settings | 1 |
| Impersonation | 2 (enter/exit) |

### Billing Routes (`billing/`)
| Route | Purpose |
|-------|---------|
| billing/ | Account overview |
| billing/plans | Plan selection |
| billing/checkout/{plan} | Checkout flow |
| billing/entitlements | View entitlements |
| billing/intents | Billing intents |
| billing/usage | Usage dashboard |

### Auth Routes
Login, register, logout, password reset, email verification, WebAuthn (register/login)

### OfficeGuy/SUMIT Routes
Checkout, callbacks, webhooks (SUMIT + Bit + CRM), document download, success page

### Twilio Voice Routes
RSVP voice connect, digit response, calling initiate/status/logs

### Other
Public event page, public RSVP page, Telescope, Pulse, Scramble API docs, robots.txt, legal pages (terms, privacy, refund)
