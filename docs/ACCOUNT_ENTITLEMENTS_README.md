# Account + Dynamic Entitlements Infrastructure (Phase 1)

Additive infrastructure only. No enforcement, gating, pricing, or UI. No changes to existing billing/checkout behavior.

---

## How the new tables relate to the current domain

- **accounts**  
  Optional layer above the current billable subject (Organization). Holds `type` (organization | individual), optional `owner_user_id`, and optional `sumit_customer_id` for future SUMIT customer mapping. Organizations can optionally belong to an account via `organizations.account_id` (nullable). Events, EventBilling, and Payment continue to be scoped by `organization_id`; `account_id` is additive and unused by current logic.

- **products**  
  Catalog of sellable products. Not linked to Plan or Event today. Used only as a source for product_entitlements (which features a product grants).

- **product_entitlements**  
  Defines which `feature_key` (free-form string) a product grants, with optional `value` and `constraints`. No predefined feature keys; keys are stored as strings.

- **account_entitlements**  
  Grants: an account has a `feature_key` (and optional value, expiry). Can reference a product_entitlement_id (grant came from a product) or be manual. No enforcement in this phase.

- **account_feature_usage**  
  Usage tracking per account, per `feature_key`, per `period_key` (e.g. YYYYMM). No enforcement in this phase.

- **billing_intents**  
  Purchase abstraction: an account has a draft/pending/completed intent, optionally linked to a payable (e.g. EventBilling) via morph. No flow uses this yet.

**Relationship to existing domain:**

- **Organization** remains the billable subject for Event and Payment creation. It may optionally have an `account_id`. No code path sets or reads `account_id` in checkout or billing services.
- **EventBilling** and **Payment** have nullable `account_id` for future use; creation still uses only `organization_id`.
- **Plan** and event checkout are unchanged; no link to Account, Product, or entitlements.

See `DB_SCHEMA_AUDIT.md`, `ACCOUNT_INSERTION_MAP.md`, and `VENDOR_CONTRACT_REQUIREMENTS.md` for evidence and vendor contract details.
