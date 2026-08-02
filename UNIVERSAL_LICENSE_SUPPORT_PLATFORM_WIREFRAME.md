# Universal License & Support Platform — Wireframe

## Overview

Product-agnostic SaaS for **license management** and **support tickets**. One Laravel backend owns data, business rules, admin UI, and public API. The customer-facing site is a separate **Qwik City** app that consumes the API.

**Design goals:** simple ops, fast customer UX, strong admin productivity, clear API for product integrations.

---

## 1. System Architecture

### 1.1 High-level layout

```
┌──────────────────────────────────────────────────────────────────┐
│                         Clients                                   │
│  ┌─────────────────────┐     ┌─────────────────────────────────┐ │
│  │ Qwik City portal    │     │ External products / SDKs        │ │
│  │ (customers)         │     │ (WP plugins, apps, APIs)        │ │
│  └──────────┬──────────┘     └──────────────┬──────────────────┘ │
└─────────────┼───────────────────────────────┼────────────────────┘
              │ HTTPS JSON                    │ API key / license
              ▼                               ▼
┌──────────────────────────────────────────────────────────────────┐
│                    Laravel application                            │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────────┐  │
│  │ Filament admin │  │ REST API /v1   │  │ Domain services    │  │
│  │ (session auth) │  │ Sanctum/tokens │  │ licenses, tickets  │  │
│  └────────────────┘  └────────────────┘  └────────────────────┘  │
│  ┌────────────────┐  ┌────────────────┐  ┌────────────────────┐  │
│  │ Queue workers  │  │ Redis cache    │  │ Mail / webhooks    │  │
│  └────────────────┘  └────────────────┘  └────────────────────┘  │
└───────────────────────────────┬──────────────────────────────────┘
                                ▼
                     MySQL 8+ (primary store)
```

### 1.2 Technology stack (locked)

| Layer | Choice | Why |
|---|---|---|
| Backend + Admin | **Laravel 12+** with **Filament 4** | One codebase for API + admin; Filament gives CRUD, filters, policies, and auth without a second SPA |
| Customer site | **Qwik City** | Resumable UI, excellent TTFB/SEO for marketing + portal; talks to Laravel over REST |
| Database | **MySQL 8+** (PostgreSQL optional later) | Matches common WAMP/VPS hosting; Laravel first-class support |
| Cache / queues | **Redis** + Laravel queues | Enough for jobs, rate limits, and sessions; avoid RabbitMQ until needed |
| Auth (admin) | Filament session (guard `web`) | Cookie session inside Laravel — no admin CORS pain |
| Auth (customer) | Laravel Sanctum personal access tokens | Stateless API for Qwik; easy logout/revoke |
| Auth (product API) | API keys (`X-API-Key` + optional secret) | For license validate/activate from shipped software |
| Payments | **Stripe first**, PayPal later | One solid gateway before multi-gateway complexity |
| Search | MySQL full-text first; Meilisearch later | Ship faster; add Meilisearch when ticket volume hurts |
| Hosting | Any PHP 8.2+ host + Node build for Qwik static/SSR | Keep infra boring |

**Explicitly deferred (optimize later, not day one):** GraphQL, Elasticsearch, multi-region, crypto payments, native mobile apps.

### 1.3 Repository layout

```
ulsp/
├── backend/                 # Laravel: API + Filament admin + jobs
│   ├── app/
│   ├── app/Filament/        # Admin resources & pages
│   ├── app/Http/Controllers/Api/V1/
│   ├── app/Services/        # LicenseService, TicketService, ...
│   └── routes/api.php
├── site-front/              # Qwik City customer portal + marketing
│   ├── src/routes/
│   ├── src/components/
│   └── src/lib/api.ts       # Typed API client → /api/v1
└── docs/                    # Optional: OpenAPI, runbooks
```

### 1.4 Request boundaries

| Surface | Base URL | Auth |
|---|---|---|
| Admin (Filament) | `https://api.example.com/admin` | Session (admin users) |
| Public/customer API | `https://api.example.com/api/v1` | Sanctum Bearer (customers) or API key (integrations) |
| Qwik site | `https://www.example.com` | Browser holds Sanctum token; never embeds admin |

---

## 2. Domain model

### 2.1 Core entities

- **User** — admin/support agents (Filament)
- **Customer** — portal end users (separate from admin users)
- **Product** — sellable software SKU
- **PricingTier** — activation limits + billing cycle
- **License** — issued key bound to customer + product
- **LicenseActivation** — domain / machine / device / API binding
- **SupportTicket** + **TicketReply** + **TicketAttachment**
- **Payment** — Stripe (and later PayPal) records
- **ApiKey** — integration credentials for products
- **WebhookDelivery** — outbound event log (optional table)

### 2.2 Product types

| Type | Activation binding |
|---|---|
| `wordpress_plugin` | Domain |
| `web_app` | Domain |
| `desktop_app` | Machine ID |
| `mobile_app` | Device ID |
| `api_service` | API key usage / rate limits |
| `saas` | Subscription seat / account (Stripe-driven) |

### 2.3 License key strategy (optimized)

Do **not** encode domain/machine into the key string. Use opaque keys; store binding in `license_activations`.

```
Format: {PRODUCT_PREFIX}-{SEGMENT}-{SEGMENT}-{SEGMENT}-{SEGMENT}
Example: WCR-A1B2-C3D4-E5F6-G7H8
```

- Prefix from product settings (human-readable)
- Segments: cryptographically random, uppercase alphanumeric
- Uniqueness enforced in DB; validation is API-driven

### 2.4 License lifecycle

```
pending → active → expired
                 → suspended
                 → cancelled
```

Rules live in `LicenseService` (not controllers): activate, deactivate, transfer, renew, suspend.

### 2.5 Ticket lifecycle

```
open → in_progress → waiting_customer → resolved → closed
```

Priorities: `low | medium | high | urgent`  
Categories: `technical | billing | feature_request | bug_report | account | license`

---

## 3. Laravel backend

### 3.1 Layering

```
HTTP (Form Requests / API Resources)
        ↓
Services / Actions (business rules)
        ↓
Eloquent models + DB transactions
        ↓
Events → Listeners / Jobs (mail, webhooks, analytics)
```

Controllers stay thin. Validation via Form Requests. Authorization via Policies + Filament shields.

### 3.2 Admin (Filament)

Ship admin **inside Laravel** (not a Vue SPA).

**Required Filament areas:**

1. **Dashboard** — licenses, open tickets, MRR/revenue snapshot, activation rate
2. **Products & pricing tiers**
3. **Customers** — CRUD, import/export, activity
4. **Licenses** — issue, suspend, transfer, renew, bulk actions
5. **Activations** — inspect/revoke bindings
6. **Tickets** — queue, assign, internal notes, SLA badges
7. **Payments** — Stripe status, refunds linkage
8. **API keys** — create/revoke/regenerate secret
9. **Users & roles** — Super Admin, Admin, Support Agent
10. **Settings** — mail, Stripe keys, SLA, branding defaults
11. **Activity log** — audit trail (spatie/laravel-activitylog or equivalent)

**Admin UX standards:**

- Server-side tables (search, sort, filters, bulk actions)
- SweetAlert-style confirms via Filament Actions
- Toast feedback for mutations
- Empty states on every list

### 3.3 API surface (`/api/v1`)

#### Public product catalog
```
GET  /products
GET  /products/{product}
```

#### Customer auth
```
POST /auth/register
POST /auth/login
POST /auth/forgot-password
POST /auth/reset-password
GET  /customer/me
PUT  /customer/profile
```

#### Customer portal (Sanctum)
```
GET  /customer/licenses
GET  /customer/licenses/{license}
GET  /customer/tickets
POST /customer/tickets
GET  /customer/tickets/{ticket}
POST /customer/tickets/{ticket}/replies
```

#### Integration / license engine (API key)
```
POST /licenses/validate
POST /licenses/activate
POST /licenses/deactivate
GET  /licenses/by-key/{license_key}/activations
GET  /licenses/by-key/{license_key}/updates
```

#### Admin JSON (optional; prefer Filament UI)
Keep only if external tools need it. Prefer Filament for humans.

```
POST /admin/login          # Sanctum token for tooling only
GET  /admin/me
# CRUD resources already covered by Filament — avoid duplicating unless required
```

#### Webhooks (inbound)
```
POST /webhooks/payment/{gateway}
```

#### Webhooks (outbound events)
```
license.activated | license.expired | ticket.created | payment.received
```

### 3.4 Auth matrix

| Actor | Mechanism | Notes |
|---|---|---|
| Admin / agent | Filament session | Roles via Filament Shield or Spatie Permission |
| Customer | Sanctum token | Issued on login/register; stored by Qwik client |
| Product integration | API key (+ secret where needed) | Scoped per product/customer; rate limited |

### 3.5 Cross-cutting backend concerns

- Rate limiting middleware (stricter on validate/activate)
- Input sanitization + secure upload for ticket attachments
- Idempotent Stripe webhooks
- Queued mail + webhook deliveries
- Structured logging; never leak secrets in API errors
- Feature flags via config/settings for optional modules (white-label later)

---

## 4. Qwik City customer site

### 4.1 Role

Qwik owns:

- Marketing / product pages (SSR + resumability)
- Customer auth UI
- License list/detail + activation helpers
- Ticket list/create/detail/reply
- Profile + password flows
- Checkout UX that calls Laravel/Stripe (Stripe Checkout or Payment Element)

Qwik does **not** own business rules for licenses or tickets — Laravel is source of truth.

### 4.2 Suggested routes

```
/                     Marketing home
/products             Catalog
/products/[slug]      Product detail + buy CTA
/login | /register | /forgot-password | /reset-password
/app                  Customer dashboard (auth)
/app/licenses
/app/licenses/[id]
/app/tickets
/app/tickets/[id]
/app/profile
/checkout/...         Payment return/cancel pages
```

### 4.3 Frontend architecture

```
site-front/
  src/
    routes/                 # file-based routing (Qwik City)
    components/ui/          # presentational
    components/forms/       # login, ticket, profile
    lib/api.ts              # fetch wrapper → NUXT-like env: PUBLIC_API_BASE
    lib/auth.ts             # token storage + route guards
    lib/types.ts            # DTO types matching API Resources
```

**Client rules:**

- `PUBLIC_API_BASE=https://api.example.com/api/v1` (must include `/api/v1`)
- Auth header: `Authorization: Bearer {token}`
- Handle 401 → clear token → `/login`
- Loading / empty / error states on every data route
- Toast for success/error; confirm destructive actions

### 4.4 Why Qwik here (optimization)

- Portal + marketing benefit from SSR and resumability (less JS on first interaction)
- Clear split: Laravel for privileged/admin work, Qwik for public/customer UX
- Avoids maintaining a second heavy SPA admin (Filament covers that)

---

## 5. Customer portal UX (wire)

### 5.1 Dashboard

- Counts: active licenses, open tickets
- Recent activity (activations, replies, payments)
- CTAs: activate license, open ticket, view invoices

### 5.2 Licenses

- List with status badges (active / expired / suspended)
- Detail: key (reveal/copy), activations, expiry, support expiry
- Actions: deactivate binding, request transfer, renew (Stripe)

### 5.3 Tickets

- Create with product/license context
- Threaded replies + attachments
- Status/priority visible; no internal notes exposed

---

## 6. Admin UX (Filament wire)

### 6.1 Dashboard widgets

- Total / active licenses
- Open tickets + urgent count
- Month revenue
- Activation success rate
- Avg first response / resolution time

### 6.2 Operational lists

- Licenses: filters by status/product/customer; bulk suspend/export
- Tickets: filters by status/priority/assignee; assign + internal note
- Customers: search, licenses count, tickets count, import/export

---

## 7. Database schema (Laravel / MySQL oriented)

Use Laravel migrations. Prefer `id` bigIncrements, `timestamps`, soft deletes where useful, and indexes on FKs + status columns.

```sql
-- products
id, name, slug UNIQUE, description, type, version, status, timestamps, soft_deletes

-- pricing_tiers
id, product_id, name, price, currency, max_activations, billing_cycle, timestamps

-- customers
id, email UNIQUE, password, first_name, last_name, company, phone, status,
email_verified_at, remember_token, timestamps, soft_deletes

-- users (admins/agents)
id, name, email UNIQUE, password, timestamps

-- licenses
id, license_key UNIQUE, product_id, customer_id, pricing_tier_id,
max_activations, status, purchased_at, expires_at, support_expires_at, timestamps

-- license_activations
id, license_id, activation_type, activation_value, activation_hash,
ip_address, user_agent, status, activated_at, last_check_at, timestamps
UNIQUE(license_id, activation_hash)

-- support_tickets
id, ticket_number UNIQUE, customer_id, license_id NULL, product_id NULL,
subject, description, priority, status, category, assigned_to NULL,
resolved_at, timestamps

-- ticket_replies
id, ticket_id, author_type (customer|user|system), author_id,
message, is_internal, timestamps

-- ticket_attachments
id, ticket_id, reply_id NULL, disk, path, filename, size, mime, uploaded_by, timestamps

-- payments
id, customer_id, license_id NULL, amount, currency, gateway, gateway_reference,
status, paid_at, meta JSON, timestamps

-- api_keys
id, customer_id NULL, product_id NULL, name, key UNIQUE, secret_hash,
rate_limit, status, last_used_at, expires_at, timestamps

-- activity_log (package or custom)
-- personal_access_tokens (Sanctum)
-- jobs / failed_jobs / cache (Laravel defaults)
```

**SLA config** can live in `settings` JSON/table rather than hardcoding.

---

## 8. Payments

### 8.1 Flow (Stripe-first)

1. Customer selects product/tier on Qwik
2. Qwik calls Laravel to create Checkout Session (or PaymentIntent)
3. Stripe hosts payment
4. Webhook `checkout.session.completed` → Laravel issues license in a DB transaction
5. Queued email with license key
6. Customer activates via portal or product plugin

### 8.2 Subscriptions

Store Stripe `subscription_id` on license/payment meta. Renewals extend `expires_at` / `support_expires_at` via webhooks. Handle `invoice.paid`, `customer.subscription.deleted`, `charge.refunded`.

---

## 9. Notifications & events

| Event | Channel |
|---|---|
| License activated / expiring / expired / suspended | Email (+ webhook) |
| Ticket created / replied / resolved | Email (+ optional Slack later) |
| Payment succeeded / failed | Email |

Use Laravel Notifications + queued listeners. Keep templates in Markdown/Blade mailables.

---

## 10. Security & compliance

- HTTPS everywhere; HSTS at edge
- Sanctum tokens + API key hashing (store secret hashes only)
- Policies on every admin/API action
- Rate limit validate/activate aggressively
- CSRF for Filament; token auth for API
- Validate/sanitize uploads (MIME, size, virus scan optional)
- Audit log for license status changes and admin impersonation-sensitive actions
- GDPR: export/delete customer data flows
- PCI: never store card data — Stripe only

---

## 11. Integrations

### 11.1 WordPress (example)

Plugin calls:

```
POST /api/v1/licenses/validate
POST /api/v1/licenses/activate
POST /api/v1/licenses/deactivate
```

with API key headers. Cache successful validation briefly client-side; always re-check on critical paths.

### 11.2 SDKs (later)

Thin clients: PHP, JS. Generate from OpenAPI when API stabilizes.

---

## 12. Multi-tenant / white-label (phase later)

Not required for MVP. When needed:

- `tenants` table + `tenant_id` on products/customers
- Branding settings (logo, colors, custom domain)
- Filament tenant switcher or separate admin domains
- Qwik reads public branding from API

Until then, run as **single-tenant** to reduce complexity.

---

## 13. Analytics (MVP → later)

**MVP (Filament widgets + SQL):** license counts, ticket SLA, Stripe revenue.

**Later:** cohort retention, churn, product funnels; warehouse export if needed.

---

## 14. Implementation phases (optimized)

### Phase 1 — Laravel foundation (1–2 weeks)
- [ ] Laravel app, MySQL, Redis, queues
- [ ] Models/migrations for products, customers, licenses, tickets
- [ ] Sanctum customer auth endpoints
- [ ] Filament install + admin users/roles
- [ ] Health endpoint + OpenAPI draft

### Phase 2 — License engine (2–3 weeks)
- [ ] Key generation service
- [ ] Validate / activate / deactivate
- [ ] Activation types + limits
- [ ] Transfer + renew stubs
- [ ] Filament license resources + bulk actions

### Phase 3 — Support (2 weeks)
- [ ] Tickets, replies, attachments
- [ ] Assignment + internal notes
- [ ] Customer ticket API
- [ ] Filament ticket desk + notifications

### Phase 4 — Qwik portal (2–3 weeks)
- [ ] Qwik City scaffold + API client
- [ ] Auth pages + dashboard
- [ ] Licenses + tickets UX
- [ ] Profile management

### Phase 5 — Payments (2 weeks)
- [ ] Stripe Checkout + webhooks
- [ ] Auto license issuance
- [ ] Invoice/history in portal + Filament

### Phase 6 — Harden & launch (2 weeks)
- [ ] Feature tests for license + ticket flows
- [ ] Rate limits, audit log, backups
- [ ] Perf pass (indexes, N+1, queue tuning)
- [ ] Docs for WP integration
- [ ] Beta → production

### Phase 7 — Expand (post-MVP)
- [ ] PayPal, Meilisearch, Slack
- [ ] White-label / multi-tenant
- [ ] SDKs, usage analytics, AI ticket routing

---

## 15. Success metrics

| Area | Target |
|---|---|
| API p95 (validate/activate) | < 200ms (cached where safe) |
| Uptime | 99.9% |
| License activation success | > 98% |
| First response (urgent) | < 1 hour |
| Portal LCP (Qwik) | Strong Core Web Vitals on marketing pages |

---

## 16. Decisions log (optimizations vs old wireframe)

| Old idea | New decision |
|---|---|
| Laravel **or** Node | **Laravel only** for backend |
| React/Vue admin SPA | **Filament admin inside Laravel** |
| Next/Nuxt portal | **Qwik City** portal |
| REST + GraphQL | **REST only** until a real GraphQL need |
| RabbitMQ / Elasticsearch day one | **Redis queues + MySQL**; add search later |
| Many payment gateways | **Stripe first** |
| Key encodes machine/domain | **Opaque keys** + activations table |
| White-label in core phases | **Post-MVP** |
| Duplicate admin JSON CRUD | Prefer **Filament**; API for customers/integrations |

---

## 17. Next steps

1. Confirm Filament vs Livewire-custom admin (default: **Filament 4**)
2. Confirm Qwik SSR host (Node adapter vs static + client auth)
3. Freeze OpenAPI for `/api/v1` auth + license endpoints
4. Start Phase 1 against this wireframe
5. Retire parallel Vue admin / Nuxt portal plans in favor of Laravel admin + Qwik site

---

## Conclusion

ULSP is a **Laravel core** (API + Filament admin + jobs) with a **Qwik City** customer front. That split maximizes admin speed and backend correctness while giving customers a fast, SEO-friendly portal — without running three overlapping frontend stacks.
