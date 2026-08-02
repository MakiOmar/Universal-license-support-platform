# Legacy reference notes (archived apps)

Archived under `archive/`:
- `backend-api-legacy/` — previous Laravel API
- `admin-dashboard-vue/` — Vue 3 admin SPA
- `site-front-nuxt/` — Nuxt 4 customer portal

## Seed credentials (legacy / new greenfield)

- Admin: `admin@ulsp.local` / `admin123` (change after first login)

## Useful API shapes to reimplement (greenfield)

Customer:
- `POST /api/v1/auth/register|login|forgot-password|reset-password`
- `GET/PUT /api/v1/customer/me|profile`
- `GET /api/v1/customer/licenses`, `GET /api/v1/customer/tickets`

Integration (API key):
- `POST /api/v1/licenses/validate|activate|deactivate`

Public:
- `GET /api/v1/products`, `GET /api/v1/products/{id}`

## Schema ideas retained

products, pricing_tiers, customers, licenses, license_activations, support_tickets, ticket_replies, ticket_attachments, payments, api_keys

## Do not port

Fat controllers, cache-based customer tokens, Vue/Nuxt runtime apps, `/api/v1/admin/*` JSON CRUD (Filament replaces admin UI).
