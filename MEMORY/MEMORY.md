# Memory Index - Kalfa.me Event SaaS Platform

**Repository:** /var/www/vhosts/kalfa.me/httpdocs
**Last Updated:** 2026-03-24

---

## 📁 Documentation Files

| File | Description |
|------|-------------|
| [`CODEBASE_ARCHITECTURE.md`](CODEBASE_ARCHITECTURE.md) | Complete architecture overview with all 91 relationships |
| [`MULTI_TENANT_ARCHITECTURE.md`](MULTI_TENANT_ARCHITECTURE.md) | Multi-tenant patterns, row-level tenancy, billing layer |

---

## 🏗️ Architecture Quick Reference

### Tech Stack
- **Framework:** Laravel 12.x
- **PHP:** 8.4
- **Database:** PostgreSQL
- **Frontend:** Livewire 4, Flux UI v2.13, TallStackUI v2
- **Auth:** WebAuthn/Passkey only (laragear/webauthn)
- **Payment:** Sumit Gateway (officeguy/laravel-sumit-gateway)
- **Voice:** Twilio integration

### Multi-Tenancy Pattern
- **Type:** Row-Level Tenancy
- **Tenant Column:** `organization_id`
- **Billing Layer:** `account_id` (separate from organizations)

### Core Models Hierarchy
```
Account (Billing)
  └── Organization (Tenant)
       └── Event (Data)
             ├── Guest
             ├── Invitation
             ├── EventTable
             └── SeatAssignment
```

### Relationship Count
- **Total Explicit Relationships:** 91
- **Models:** 31
- **Types:** BelongsTo (42), HasMany (35), BelongsToMany (3), HasOne (3), MorphTo (5), MorphMany (3)

---

## 🎯 Key Services

| Service | Purpose |
|---------|---------|
| `FeatureResolver` | Hierarchical feature flag resolution with caching |
| `BillingService` | Event payment lifecycle management |
| `SubscriptionService` | Trial and subscription operations |
| `UsageMeter` | Usage tracking and overage billing |
| `OrganizationContext` | Current organization management |
| `OrganizationMemberService` | Member invitations and role management |
| `CallingService` | Twilio voice integration for RSVP |
| `CouponService` | Coupon validation and redemption |

---

## 🔑 Domain Enums

| Enum | Values |
|------|--------|
| `EventStatus` | draft, pending_payment, active, locked, archived, cancelled |
| `AccountSubscriptionStatus` | trial, active, past_due, cancelled |
| `OrganizationUserRole` | owner, admin, member |
| `RsvpResponseType` | yes, no, maybe |
| `ProductPriceBillingCycle` | monthly, yearly, usage |

---

## 📋 Middleware Stack

```
auth → verified → ensure.organization → ensure.account_active → Route
```

**Special Middleware:**
- `system.admin` - System admin routes
- `require.impersonation` - System admins must impersonate orgs
- `SpatiePermissionTeam` - Sets team_id = organization_id

---

## 💡 Key Insights

1. **No Global Scopes** - All tenant scoping is explicit in queries
2. **Billing Isolation** - Account entity separate from Organization
3. **Explicit Scoping Required** - Developers must manually include `organization_id` in queries
4. **Cache-Heavy** - Feature resolution (300s TTL), billing access (60s TTL)
5. **Polymorphic Relations** - Payment, BillingIntent, CouponRedemption use morphTo
6. **Passkey-Only Auth** - No password authentication, WebAuthn/passkeys only

---

## 🔗 External Integrations

- **Twilio** - Voice RSVP via phone
- **SUMIT Gateway** - Payment processing (officeguy/laravel-sumit-gateway)
- **Spatie Permissions** - Role-based permissions with team scoping
- **Spatie MediaLibrary** - Event image management

---

## 📝 Last Session Activity

**Date:** 2026-03-24
**Tasks Completed:**
1. Comprehensive codebase understanding
2. All 31 models audited for relationships
3. Multi-tenant architecture documented
4. Complete relationship map created (91 relationships)
5. Memory documentation saved

**Next Session Focus:**
- Continue learning specific features
- Implement improvements based on architecture understanding
- Consider CRM integration patterns
