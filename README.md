# Universal License Support Platform (ULSP)

Greenfield MVP: **Laravel 12 + Filament 4** admin/API and a **Qwik City** customer portal.

## Stack

| Layer | Technology |
|---|---|
| Backend + Admin | Laravel 12, Filament 4 (`/admin`), Sanctum, Spatie Permission/Activitylog |
| Customer site | Qwik City (`site-front`) |
| Database | MySQL 8 |
| Queues / cache | Database drivers by default (Redis-ready) |
| Payments | Stripe Checkout + webhooks |
| Integrations | API keys (`X-API-Key`) for license validate/activate |

Legacy Vue admin, Nuxt portal, and old API live under [`archive/`](archive/) (reference only).

## Structure

```
ulsp/
├── backend/           # Laravel API + Filament
├── site-front/        # Qwik City portal
├── archive/           # Previous apps (do not run as primary)
├── docs/
│   ├── openapi/openapi.yaml
│   ├── MOBILE_APP_LICENSE_INTEGRATION.md
│   └── reference-notes.md
└── UNIVERSAL_LICENSE_SUPPORT_PLATFORM_WIREFRAME.md
```

## Quick start

### Backend

```bash
cd backend
composer install
cp .env.example .env   # if needed
php artisan key:generate
# Configure MySQL: DB_DATABASE=ulsp, DB_USERNAME=root
php artisan migrate:fresh --seed
php artisan serve
```

- API: `http://127.0.0.1:8000/api/v1`
- Admin: `http://127.0.0.1:8000/admin`
- OpenAPI: [`docs/openapi/openapi.yaml`](docs/openapi/openapi.yaml)
- Mobile license guide: [`docs/MOBILE_APP_LICENSE_INTEGRATION.md`](docs/MOBILE_APP_LICENSE_INTEGRATION.md)

**Seeded accounts**

| Role | Email | Password |
|---|---|---|
| Super admin | `admin@ulsp.local` | `admin123` |
| Customer | `customer@ulsp.local` | `password` |

Change the admin password after first login.

Optional: `php artisan queue:work` for queued mail/notifications.

### Customer portal (Qwik)

```bash
cd site-front
npm install
```

API target is chosen by script (Qwik always uses Vite `--mode ssr` for the dev server):

| Script | API base |
|---|---|
| `npm run dev` / `npm run dev:local` | `http://127.0.0.1:8000/api/v1` |
| `npm run dev:production` | `https://ulsp.gamesspoteg.com/api/v1` |
| `npm run preview` / `npm run preview:local` | local API |
| `npm run preview:production` | production API |

Env files: [site-front/.env.development](site-front/.env.development), [site-front/.env.production-api](site-front/.env.production-api), [site-front/.env.production](site-front/.env.production). Override with `.env.*.local` (gitignored).

Portal defaults to Vite SSR on port **5173**.

```bash
npm run dev                 # local backend
npm run dev:production      # hit production API while developing UI
npm run preview:local
npm run preview:production
```

## API overview

- **Public:** `GET /products`, `GET /products/{id\|slug}`
- **Customer auth:** `POST /auth/register|login|forgot-password|reset-password`
- **Customer (Bearer):** `/customer/me`, `/customer/licenses`, `/customer/tickets`, `POST /checkout/session`
- **Integration (`X-API-Key`):** `POST /licenses/validate|activate|deactivate`
- **Webhooks:** `POST /webhooks/payment/stripe`

There is **no** `/api/v1/admin/*` CRUD — operators use Filament.

## Stripe

Set in `backend/.env`:

```
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
FRONTEND_URL=http://localhost:5173
```

Checkout success/cancel URLs are under the Qwik app (`/checkout/success`, `/checkout/cancel`).

## Tests

```bash
cd backend
php artisan test
```

## Docs

- Wireframe: [`UNIVERSAL_LICENSE_SUPPORT_PLATFORM_WIREFRAME.md`](UNIVERSAL_LICENSE_SUPPORT_PLATFORM_WIREFRAME.md)
- OpenAPI: [`docs/openapi/openapi.yaml`](docs/openapi/openapi.yaml)
- Mobile license activate/validate: [`docs/MOBILE_APP_LICENSE_INTEGRATION.md`](docs/MOBILE_APP_LICENSE_INTEGRATION.md)
- Legacy notes: [`docs/reference-notes.md`](docs/reference-notes.md)
