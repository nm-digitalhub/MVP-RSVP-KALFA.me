# KALFA Architecture Documentation

This directory contains technical architecture documentation for the KALFA SaaS platform.

## Directory Structure

### `/APIs`
RESTful API endpoint documentation, schemas, and versioning information.

### `/Services`
Service layer architecture, business logic documentation, and service contracts.

### `/Database`
Database schema, migrations, relationships, and data flow documentation.

### `/Infrastructure`
Deployment, CI/CD, monitoring, and infrastructure-as-code documentation.

---

## Quick Links

- [[Architecture/Overview|System Overview]] - Full architecture overview
- [[Architecture/APIs/REST-API|REST API Reference]] - All endpoints
- [[Architecture/Database/Schema|Database Schema]] - Tables & relationships
- [[Architecture/Services/BillingService|BillingService]] - Payment flow
- [[Architecture/Services/FeatureResolver|FeatureResolver]] - Product Engine & entitlements
- [[Architecture/Services/OrganizationContext|OrganizationContext]] - Multi-tenancy
- [[Architecture/Services/Notifications|Notifications]] - WhatsApp, Voice, SMS
- [[Architecture/Permissions|Permissions System]] - Roles & authorization
- [[Architecture/Infrastructure|Infrastructure]] - Deployment & dev setup
- [[Architecture/ADRs/]] - Architecture Decision Records

---

## ADR Index

Architecture Decision Records (ADRs) are tracked in this vault with the tag `#adr`.

ADR files live in `Architecture/ADRs/` using the format `ADR-YYYYMMDD-short-identifier`.

---

## System Overview

**Platform:** KALFA - Multi-tenant RSVP & Seating SaaS
**Tech Stack:**
- Frontend: Livewire 4 + Alpine.js + Tailwind CSS v4
- Backend: Laravel 12 (PHP 8.4)
- Database: PostgreSQL
- Queue: Redis
- Notifications: Twilio (SMS/WhatsApp/Voice)
- Payment: SUMIT Gateway

**Key Systems:**
1. **Multi-tenancy** - Organization-based tenant isolation
2. **Event Management** - Event lifecycle, guests, invitations
3. **Seating System** - Tables, seat assignments, visual layout
4. **RSVP System** - Guest responses, voice RSVP (Twilio + Gemini Live)
5. **Billing** - Event payments, subscriptions, webhooks
6. **Notifications** - SMS, WhatsApp, email, voice calls

---

## Documentation Conventions

### Naming ADRs
Use the `Templates/Architecture.md` template for all architecture decisions.

Format: `ADR-YYYYMMDD-short-identifier`

Example: `ADR-20260312-multi-organization-context`

### API Documentation
Each major endpoint or endpoint group should have its own doc in `/APIs/`.

Include:
- Endpoint path & methods
- Request/response schemas
- Authentication requirements
- Rate limiting
- Error responses

### Service Documentation
Document services in `/Services/` with:
- Purpose & responsibility
- Dependencies (other services, external APIs)
- Public methods/contracts
- Error handling strategy
- Testing approach

---

## Related
- [[Projects/]] - Active projects implementing architecture
- [[Knowledge/]] - General knowledge base
- [[Tasks/]] - Development tasks
- [[Daily/]] - Daily development logs
