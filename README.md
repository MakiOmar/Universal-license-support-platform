# Universal License Support Platform (ULSP)

A comprehensive SaaS platform for managing software licenses, support tickets, and customer relationships across multiple products.

## 📋 Table of Contents

- [Overview](#overview)
- [Project Structure](#project-structure)
- [Technology Stack](#technology-stack)
- [What's Been Completed](#whats-been-completed)
- [What's Next](#whats-next)
- [Setup Instructions](#setup-instructions)
- [API Documentation](#api-documentation)
- [Development Guidelines](#development-guidelines)

## 🎯 Overview

ULSP is a multi-product license management and support ticket platform that allows you to:
- Manage multiple software products from a single dashboard
- Generate and validate license keys for different product types
- Handle customer support tickets
- Track license activations and usage
- Process payments and subscriptions
- Provide API access for external integrations

## 📁 Project Structure

```
ulsp/
├── backend/              # Laravel API backend
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/
│   │   │   ├── Middleware/
│   │   │   └── Requests/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Http/Resources/
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── factories/
│   └── routes/
│       └── api.php
├── admin-dashboard/      # Vue.js admin dashboard
│   ├── src/
│   │   ├── views/
│   │   ├── layouts/
│   │   ├── stores/
│   │   ├── services/
│   │   └── router/
│   └── package.json
├── site-front/           # Nuxt.js public-facing site (TODO)
└── README.md

```

## 🛠 Technology Stack

### Backend
- **Framework**: Laravel 12.40.2
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum (for admin), JWT (for customers)
- **API**: RESTful API with versioning (v1)

### Admin Dashboard
- **Framework**: Vue 3 + TypeScript
- **Build Tool**: Vite
- **State Management**: Pinia
- **Routing**: Vue Router
- **Styling**: Tailwind CSS
- **HTTP Client**: Axios

### Customer Portal (Nuxt.js)
- **Framework**: Nuxt.js 4.2.1
- **State Management**: Pinia
- **Styling**: Tailwind CSS (via @nuxt/ui)
- **UI Components**: Nuxt UI
- **HTTP Client**: Fetch API with composable wrapper

## ✅ What's Been Completed

### Backend (Laravel API)

#### Core Features
- ✅ **Database Schema**: Complete migrations for all core tables
  - Products, Customers, Licenses, License Activations
  - Support Tickets, Ticket Replies, Ticket Attachments
  - Payments, API Keys
  - Users (for admin authentication)

- ✅ **Models & Relationships**: All Eloquent models with relationships
  - Product, Customer, License, LicenseActivation
  - SupportTicket, TicketReply, TicketAttachment
  - Payment, ApiKey, User

- ✅ **API Controllers**: Full CRUD operations
  - ProductController (with public access, search functionality)
  - CustomerController (with import/export, customer-specific routes)
  - LicenseController (with activation/deactivation, transfer, validation)
  - TicketController (with replies, status updates, attachments)
  - PaymentController (with Stripe and PayPal integration, webhook handling)
  - ApiKeyController (full CRUD for API key management)
  - AdminAuthController (Sanctum authentication)
  - AuthController (Customer JWT authentication)
  - WebhookController

- ✅ **API Routes**: Complete route structure
  - Admin routes (`/api/v1/admin/*`) - Protected with Sanctum
  - Public API routes (`/api/v1/*`) - Protected with API keys
  - Customer auth routes (`/api/v1/auth/*`)
  - Customer portal routes (`/api/v1/customer/*`) - Protected with customer token
  - Public product routes (`/api/v1/products`) - No auth required
  - Webhook routes (`/api/v1/webhooks/*`)
  - Customer import/export routes (`/api/v1/admin/customers/import`, `/export`)
  - License transfer routes (`/api/v1/admin/licenses/{id}/transfer`)
  - API key management routes (`/api/v1/admin/api-keys/*`)
  - Payment webhook routes (`/api/v1/webhooks/payment/{gateway}`)
  - Ticket assignment routes (`/api/v1/admin/tickets/{ticket}/assign`)
  - Admin list route (`/api/v1/admin/admins`)

- ✅ **Authentication & Security**
  - Laravel Sanctum for admin dashboard authentication
  - API key authentication for external integrations
  - Rate limiting middleware
  - Input sanitization middleware
  - Secure file upload middleware
  - CORS configuration for localhost development

- ✅ **Services & Business Logic**
  - LicenseKeyGenerator (product-type specific key generation)
  - LicenseActivationService (activation/deactivation logic)
  - StripePaymentService (Stripe payment integration)
  - PayPalPaymentService (PayPal payment integration)
  - CacheService (caching for performance)
  - Queue-based background processing (customer import, email sending)
  - Period-based date calculation (license expiration)
  - Customer authentication middleware (token validation)

- ✅ **Email Notification System**
  - Queue-based email sending (SendEmailJob)
  - HTML email templates (Blade)
  - License activation notifications
  - License expiration warnings (scheduled at 30, 7, and 1 days before expiration)
  - Ticket creation/update notifications (including assignment notifications)
  - Password reset emails
  - Payment confirmation emails
  - Import completion notifications
  - Retry mechanism with exponential backoff
  - Error logging for failed emails

- ✅ **Payment Integration**
  - Stripe payment integration (payment intents, webhook handling, signature verification)
  - PayPal payment integration (Orders API v2, webhook handling, signature verification)
  - Payment intent/order creation with metadata
  - Webhook signature verification
  - Automatic license creation on successful payment
  - Payment confirmation emails
  - Generic webhook handler for other payment gateways

- ✅ **Scheduled Tasks**
  - License expiration notification checks (daily at 9 AM)
  - Configurable notification windows (30, 7, 1 days)
  - Duplicate notification prevention via cache

- ✅ **Validation & Resources**
  - Form Request validation classes
  - API Resource classes for standardized responses
  - Null-safe date handling in all resources
  - Period-based expiration validation

- ✅ **Performance & Security**
  - Database indexes on frequently queried columns
  - Query optimization with eager loading
  - Caching for license validation
  - Pagination limits
  - File upload security (MIME validation, size limits)
  - Queue workers for background processing
  - Route order optimization for API endpoints

- ✅ **Database Seeders**
  - AdminUserSeeder (creates default admin user)
  - DatabaseSeeder (creates demo data)

- ✅ **Queue System**
  - Laravel queue configuration
  - Background job processing
  - Customer import job (ImportCustomersJob)
  - Job result caching and status tracking

### Admin Dashboard (Vue.js)

#### Core Features
- ✅ **Authentication System**
  - Login page with email/password
  - Token-based authentication with Sanctum
  - Protected routes with auth guards
  - Auto-logout on token expiration

- ✅ **Dashboard Layout**
  - Sidebar navigation
  - Header with user info
  - Responsive design

- ✅ **Dashboard Page**
  - Statistics cards (Total Licenses, Active Licenses, Open Tickets, Revenue)
  - Real-time data from API

- ✅ **Product Management**
  - List view with search
  - Create product modal form
  - Delete functionality
  - Product types: WordPress Plugin, Web App, Desktop App, Mobile App, API Service, SaaS Product

- ✅ **Product Management**
  - List view with search
  - Create product modal form
  - Edit product functionality
  - Delete functionality with confirmation
  - Product types: WordPress Plugin, Web App, Desktop App, Mobile App, API Service, SaaS Product

- ✅ **License Management**
  - List view with search and status filtering
  - Create license form with period-based expiration (number + unit)
  - Edit license functionality
  - License transfer functionality
  - Detail view with license information
  - Activation history display
  - Expiration dates calculated from purchased date + period

- ✅ **Ticket Management**
  - List view with search, status, and priority filters
  - Create ticket modal form
  - Detail view with ticket information
  - Add replies with status/priority updates
  - Close ticket functionality
  - Assign tickets to admins (with UI in list and detail views)
  - Quick status update actions (Mark In Progress, Mark Resolved)
  - Display assigned admin information
  - Replies display

- ✅ **Customer Management**
  - List view with search
  - Create customer modal form
  - Edit customer functionality
  - Delete customer with confirmation
  - Detail view with customer information
  - Associated licenses display
  - **Import/Export Functionality**
    - CSV export (download all customers)
    - CSV import with queue-based background processing
    - Import status tracking
    - Flexible CSV column mapping
    - Error reporting for failed imports

- ✅ **UI/UX Enhancements**
  - SweetAlert2 integration for all confirmations
  - Toast notifications for success/error messages
  - Consistent alert system across admin dashboard
  - Route watchers for automatic data refresh
  - Empty state messages
  - Improved error handling

- ✅ **API Integration**
  - Axios service with interceptors
  - Automatic token injection
  - Error handling
  - Admin API base URL configuration

### Customer Portal (Nuxt.js)

#### Core Features
- ✅ **Authentication System**
  - Registration page with validation
  - Login page with token management
  - Password reset flow
  - Token-based authentication (stored in localStorage)
  - Protected routes with middleware
  - Auto-logout on token expiration

- ✅ **Layout & Navigation**
  - Responsive navigation bar
  - Conditional menu items (based on auth status)
  - User info display
  - Logout functionality

- ✅ **Dashboard Page**
  - Statistics cards (licenses, tickets, expired licenses)
  - Recent licenses list
  - Real-time data from customer API

- ✅ **Product Pages**
  - Product listing with search functionality
  - Product detail page
  - Public access (no authentication required)
  - Security: Only active products visible

- ✅ **License Management**
  - List view with card layout
  - License detail page with full information
  - Activation history display
  - Status badges and visual indicators

- ✅ **Support Tickets**
  - List view with status and priority filters
  - Create ticket modal form
  - Ticket detail page with replies
  - Add reply functionality
  - Real-time updates

- ✅ **Profile Settings**
  - Edit account information
  - Change password with confirmation
  - Form validation
  - Real-time auth store updates

- ✅ **UI/UX Features**
  - SweetAlert2 integration for confirmations and toasts
  - Error page with user-friendly messages
  - Loading states and empty states
  - Responsive design
  - Debounced search functionality

- ✅ **Security & Performance**
  - Token validation on all authenticated requests
  - Automatic 401 handling and redirect
  - Error handling with user-friendly messages
  - Optimized API calls with proper error extraction
  - Public route handling

## 🚧 What's Next

### High Priority

#### 1. Nuxt.js Public Site (site-front/)
- [x] **Project Setup**
  - [x] Initialize Nuxt.js 3 project
  - [x] Configure Tailwind CSS (via @nuxt/ui)
  - [x] Set up routing
  - [x] Configure API client with error handling

- [x] **Customer Authentication**
  - [x] Registration page
  - [x] Login page
  - [x] Password reset flow
  - [x] Token management (localStorage)
  - [x] Protected routes middleware
  - [x] Auth store (Pinia)

- [x] **Customer Portal Pages**
  - [x] Dashboard/Home page with statistics
  - [x] My Licenses page (list view)
  - [x] License detail page with activation history
  - [x] Support Tickets list with filters
  - [x] Create ticket functionality
  - [x] Ticket detail page (with replies)
  - [x] Profile/Settings page with password change

- [x] **Product Pages**
  - [x] Product listing page with search
  - [x] Product detail page
  - [x] Public access (no auth required)
  - [ ] Purchase/Checkout flow (basic structure ready)
  - [ ] Payment integration (pending)

#### 2. Backend Enhancements
- [x] **Payment Integration**
  - [x] Stripe integration (payment intents, webhook handling, signature verification)
  - [x] PayPal integration (Orders API v2, webhook handling, signature verification)
  - [x] Payment webhook handlers (Stripe and PayPal implemented, generic handler for others)
  - [ ] Subscription management

- [x] **Email Notifications**
  - [x] License activation emails (queue-based)
  - [x] Ticket creation/update emails (queue-based)
  - [x] Password reset emails (queue-based)
  - [x] Payment confirmation emails (queue-based)
  - [x] HTML email templates (Blade)
  - [x] Queue-based email sending for performance
  - [x] Import completion notifications (email sent to admin on completion)

- [ ] **Advanced Features**
  - [x] License transfer functionality
  - [x] Period-based expiration calculation
  - [x] Customer import/export (CSV)
  - [ ] License renewal system
  - [ ] Usage analytics and reporting
  - [ ] Export functionality (PDF reports)
  - [ ] Bulk operations for licenses

#### 3. Admin Dashboard Enhancements
- [ ] **License Management**
  - [x] Create license form
  - [x] Edit license functionality
  - [x] License transfer UI
  - [ ] Bulk operations
  - [ ] License renewal system
  - [x] License expiration notifications (scheduled daily checks at 30, 7, and 1 days before expiration)

- [ ] **Ticket Management**
  - [x] Reply to tickets
  - [x] Change ticket status/priority
  - [x] Close ticket functionality
  - [x] Assign tickets to admins (backend and UI)
  - [ ] File attachment support (backend ready, UI pending)
  - [x] Ticket assignment UI

- [ ] **Customer Management**
  - [x] Create customer form
  - [x] Edit customer functionality
  - [x] Import/Export functionality
  - [ ] Customer activity log
  - [ ] Bulk customer operations

- [x] **API Key Management**
  - [x] List API keys with search and filters
  - [x] Create API keys (with customer and product association)
  - [x] Edit API keys (rate limit, status, expiration)
  - [x] Regenerate API secrets
  - [x] Delete API keys
  - [x] View API key details (last used, expiration)

- [ ] **Analytics & Reports**
  - [ ] Revenue charts
  - [ ] License statistics
  - [ ] Ticket metrics
  - [ ] Export reports (CSV export for customers done, others pending)
  - [ ] Dashboard charts and graphs

### Medium Priority

#### 4. Additional Features
- [ ] **White-label System**
  - [ ] Custom branding per product
  - [ ] Custom domains
  - [ ] Custom email templates

- [ ] **API Enhancements**
  - [ ] GraphQL endpoint (optional)
  - [ ] Webhook system for external integrations
  - [ ] API documentation (Swagger/OpenAPI)

- [ ] **Testing**
  - [ ] Unit tests for backend
  - [ ] Integration tests for API
  - [ ] E2E tests for dashboard
  - [ ] E2E tests for public site

- [ ] **Documentation**
  - [ ] API documentation
  - [ ] Admin user guide
  - [ ] Customer portal guide
  - [ ] Developer documentation

### Low Priority

#### 5. Advanced Features
- [ ] **Multi-language Support**
  - [ ] i18n for admin dashboard
  - [ ] i18n for public site
  - [ ] Language switcher

- [ ] **Advanced Analytics**
  - [ ] Real-time dashboards
  - [ ] Custom report builder
  - [ ] Data visualization

- [ ] **Mobile Apps**
  - [ ] React Native admin app
  - [ ] React Native customer app

## 🚀 Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 8.0+
- WAMP/XAMPP (for Windows) or LAMP (for Linux)

### Backend Setup

1. **Navigate to backend directory**
   ```bash
   cd backend
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Update `.env` file**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ulsp
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed database**
   ```bash
   php artisan db:seed --class=AdminUserSeeder
   ```

7. **Start server**
   ```bash
   php artisan serve
   ```

   Backend will be available at `http://127.0.0.1:8000`

### Admin Dashboard Setup

1. **Navigate to admin-dashboard directory**
   ```bash
   cd admin-dashboard
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Start development server**
   ```bash
   npm run dev
   ```

   Dashboard will be available at `http://localhost:5173`

4. **Login credentials**
   - Email: `admin@ulsp.local`
   - Password: `admin123`

### Public Site Setup (TODO)

1. **Navigate to site-front directory**
   ```bash
   cd site-front
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Start development server**
   ```bash
   npm run dev
   ```

## 📚 API Documentation

### Base URL
```
http://127.0.0.1:8000/api/v1
```

### Authentication

#### Admin Authentication (Sanctum)
```http
POST /admin/login
Content-Type: application/json

{
  "email": "admin@ulsp.local",
  "password": "admin123"
}
```

Response:
```json
{
  "user": {
    "id": 1,
    "name": "Administrator",
    "email": "admin@ulsp.local"
  },
  "token": "1|...",
  "token_type": "Bearer"
}
```

Use token in subsequent requests:
```http
Authorization: Bearer {token}
```

#### API Key Authentication
```http
X-API-Key: {your_api_key}
```
or
```http
Authorization: Bearer {your_api_key}
```

### Key Endpoints

#### Products
- `GET /admin/products` - List products (admin)
- `POST /admin/products` - Create product (admin)
- `GET /admin/products/{id}` - Get product (admin)
- `PUT /admin/products/{id}` - Update product (admin)
- `DELETE /admin/products/{id}` - Delete product (admin)

#### Licenses
- `GET /admin/licenses` - List licenses (admin)
- `POST /admin/licenses` - Create license (admin)
- `GET /admin/licenses/{id}` - Get license (admin)
- `POST /admin/licenses/activate` - Activate license
- `POST /admin/licenses/deactivate` - Deactivate license
- `GET /admin/licenses/validate?license_key={key}` - Validate license

#### Customers
- `GET /admin/customers` - List customers (admin)
- `POST /admin/customers` - Create customer (admin)
- `GET /admin/customers/{id}` - Get customer (admin)

#### Tickets
- `GET /admin/tickets` - List tickets (admin)
- `POST /admin/tickets` - Create ticket
- `GET /admin/tickets/{id}` - Get ticket (admin)
- `POST /admin/tickets/{id}/replies` - Add reply (admin)

## 🧪 Development Guidelines

### Code Standards
- Follow WordPress PHP Coding Standards (WPCS) for backend
- Use Arial or default browser fonts (no Google Fonts)
- Commit after each feature completion

### Git Workflow
- Main branch: `master`
- Commit messages should be descriptive
- Commit after completing each feature

### Database
- Always create migrations for schema changes
- Use factories for test data
- Use seeders for initial data

### API Versioning
- Current version: `v1`
- All routes under `/api/v1/`
- Admin routes under `/api/v1/admin/`

## 📝 Notes

- Default admin user is created via `AdminUserSeeder`
- CORS is configured for localhost development
- Sanctum is used for SPA authentication
- API keys are used for external integrations
- File uploads are limited to 10MB with MIME type validation

## 🔗 Related Documentation

- [CORS Configuration](./CORS_CONFIGURATION.md)
- [Wireframe Documentation](./UNIVERSAL_LICENSE_SUPPORT_PLATFORM_WIREFRAME.md)

## 📞 Support

For issues or questions, please refer to the wireframe documentation or create an issue in the repository.

---

**Last Updated**: December 2, 2025
**Version**: 1.1.0

## 📝 Recent Updates (December 2025)

### Completed Features
- ✅ **Customer Import/Export**: CSV import with queue-based processing and export functionality
- ✅ **Period-Based License Expiration**: Set expiration using number + unit (days/months/years) calculated from purchased date
- ✅ **Complete CRUD Operations**: Full create, edit, delete functionality for all entities
- ✅ **SweetAlert2 Integration**: Consistent confirmation dialogs and toast notifications across admin dashboard and customer portal
- ✅ **License Transfer**: Transfer licenses between customers
- ✅ **Ticket Management**: Reply to tickets, update status/priority, close tickets, assign tickets to admins
- ✅ **Error Handling**: Fixed null date errors in all API resources
- ✅ **Route Optimization**: Fixed route order for import/export endpoints
- ✅ **Stripe Payment Integration**: Payment intents, webhook handling, automatic license creation
- ✅ **PayPal Payment Integration**: Orders API v2, webhook handling, automatic license creation
- ✅ **API Key Management**: Full CRUD with admin dashboard UI, secret regeneration, expiration tracking
- ✅ **License Expiration Notifications**: Scheduled daily checks with email notifications
- ✅ **Import Completion Notifications**: Email notifications sent to admins when imports complete
- ✅ **Ticket Assignment**: Assign tickets to admin users with UI in both list and detail views
- ✅ **Customer Portal**: Complete Nuxt.js customer portal with authentication, dashboard, licenses, tickets, and profile pages
- ✅ **Product Pages**: Public product listing and detail pages with search functionality

