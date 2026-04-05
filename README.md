# StripeDesk

> A Stripe-powered commerce API built with CodeIgniter 3, PHP 7.3, and MySQL 8.
> Supports product and user management, Stripe Checkout, invoice/receipt generation,
> email notifications, and a REST API — all containerised with Docker.

---

## Table of Contents

1. [Tech Stack](#tech-stack)
2. [Public deployment](#public-deployment)
3. [Payment fulfillment resilience](#payment-fulfillment-resilience)
4. [OTP verification & SMTP (production note)](#otp-verification--smtp-production-note)
5. [Installation & Setup](#installation--setup)
6. [Running Locally with Ngrok & Stripe](#running-locally-with-ngrok--stripe)
7. [Stripe Test Mode Walkthrough](#stripe-test-mode-walkthrough)
8. [API documentation](#api-documentation)
9. [Security & Task Restriction Design](#security--task-restriction-design)
10. [Mobile API Considerations](#mobile-api-considerations)
11. [Staging vs Production Deployment](#staging-vs-production-deployment)
12. [Additional Tools & Libraries](#additional-tools--libraries)
13. [Email (SMTP for OTP)](#email-smtp-for-otp)
14. [Project Structure](#project-structure)
15. [Quick Reference](#quick-reference)

---

## Tech Stack

| Layer        | Technology                                   |
|--------------|----------------------------------------------|
| Framework    | CodeIgniter 3.1.13                           |
| Language     | PHP 7.3 (FPM)                                |
| Database     | MySQL 8.0                                    |
| Web Server   | Nginx 1.25 (Alpine)                          |
| Payments     | Stripe PHP SDK ^7.128 (Checkout + Webhooks)  |
| Auth (API)   | Firebase JWT (HS256) + httpOnly auth cookies |
| PDF receipts | Dompdf ^2.x (HTML → PDF)                       |
| Email        | PHPMailer 6 (+ optional MailHog in dev)      |
| Container    | Docker + Docker Compose                      |
| Tunnel       | Ngrok (Stripe webhook in local dev)          |
| Admin / shop UI | Vue 3 SPA (**StripeDesk_FrontEnd** repo), not server-rendered |
| Server views | CI error pages only (`application/views/errors/`) |

---

## Public deployment

Reference deployment (same codebase family as this repository):

| Environment | URL |
|-------------|-----|
| **Web portal (Vue SPA)** | [https://stripedesk.duolinkmm.com/](https://stripedesk.duolinkmm.com/) |
| **REST API + Swagger UI** | [https://api-stripedesk.duolinkmm.com/docs/#/](https://api-stripedesk.duolinkmm.com/docs/#/) |

Configure the SPA with `VITE_API_BASE_URL` pointing at the API origin (e.g. `https://api-stripedesk.duolinkmm.com/api/v1`). Set `CORS_ALLOW_ORIGIN` on the API to include the SPA origin.

---

## Payment fulfillment resilience

Stripe Checkout completion can be observed through **webhooks**, the **browser return URL**, and **retries**. StripeDesk uses one **idempotent** code path so invoice/receipt creation does not double-run.

| Layer | What it handles |
|-------|-----------------|
| **Idempotency in fulfillment** | `Checkout_session_fulfillment_service::apply()` skips work when the order is already `paid`, so Stripe webhook retries and duplicate delivery do not create duplicate invoices/receipts. |
| **Webhook** | `POST /api/v1/stripe/webhook` — primary path when Stripe delivers `checkout.session.completed` (signature verified when `STRIPE_WEBHOOK_SECRET` is set). |
| **Active reconcile on success page** | After redirect, the SPA calls `POST /api/v1/checkout/reconcile` with `session_id` so a paid session is fulfilled even if the webhook is **slower than the user’s redirect** (race). |
| **Shared `apply()` method** | Webhook handler and reconcile/cron all call the same fulfillment service — **single fulfillment logic**, no duplicated business rules. |
| **Pending checkout page** | If the session is not yet `paid`/`complete` in Stripe when reconcile runs, the user stays on a **pending** flow instead of a false success. |
| **Cron reconciliation** | CLI: `php public/index.php cron stripe_reconcile [token]` — polls Stripe for pending orders with a Checkout session (e.g. every **15 minutes** in production). Covers **server down** during the webhook window. |
| **Stripe retries** | Stripe retries webhooks for an extended period (on the order of **days**); combined with cron + client reconcile, **short outages** recover without manual DB fixes. |

Implementation entry points: `application/Stripedesk/Services/Checkout_session_fulfillment_service.php`, `Stripe_webhook_service.php`, `Checkout_reconcile_service.php`, `application/controllers/Cron.php`.

---

## OTP verification & SMTP (production note)

On some hosts (e.g. **DigitalOcean** without a transactional email add-on), **outbound SMTP from the droplet may be blocked or unreliable** on free/low tiers, so OTP emails might not be delivered.

For that reason the API supports a **strictly opt-in** development flag:

```env
# .env — NEVER enable in a real production environment
OTP_DEV_RETURN_CODE=true
```

When `OTP_DEV_RETURN_CODE` is `true`, successful OTP-related API responses can include the **OTP code in the JSON body** (see `Auth_password_service`) so testers can complete registration/password reset **without email**. **Turn this off** as soon as a proper SMTP provider (SendGrid, Mailgun, SES, Postmark, etc.) is configured (`MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, … in `.env`).

**Planned improvement:** move to a dedicated mail provider and keep `OTP_DEV_RETURN_CODE=false` in production so OTPs are **email-only**.

---

## Installation & Setup

### Prerequisites

Make sure the following are installed on your macOS machine:

```bash
docker --version          # Docker Desktop 4.x+
docker compose version    # Compose v2+
ngrok --version           # Ngrok v3+
```

Install Ngrok if needed:

```bash
brew install ngrok/ngrok/ngrok
ngrok config add-authtoken YOUR_NGROK_AUTH_TOKEN
```

---

### Step 1 — Clone & configure environment

```bash
git clone https://github.com/yourname/stripedesk.git
cd stripedesk
cp .env.example .env
```

Open `.env` and fill in your Stripe test keys (see [Stripe Test Mode Walkthrough](#stripe-test-mode-walkthrough)):

```env
STRIPE_SECRET_KEY=sk_test_REPLACE_ME
STRIPE_WEBHOOK_SECRET=whsec_REPLACE_ME     # filled in after Step 4
JWT_SECRET=your_random_32_char_secret_here
APP_URL=http://localhost:8081
```

> All other values (DB credentials, SMTP) have working defaults for local development — no changes needed.

---

### Step 2 — Build & start containers

```bash
docker compose up -d --build
```

This starts **nginx**, **php-fpm**, and **MySQL** (see `docker-compose.yml`):

| Service / container | Purpose | Port(s) |
|---------------------|---------|---------|
| `nginx` / `stripedesk-nginx` | Reverse proxy → PHP | `8081` host → `80` (override with `WEB_PORT` in `.env`) |
| `php` / `stripedesk-php` | PHP-FPM + CodeIgniter | internal only |
| `db` / `stripedesk-db` | MySQL 8 | `3306` (override with `DB_PORT` in `.env`) |

Default database credentials match Docker Compose env defaults (`MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`, typically `stripedesk` / `stripedesk` / `stripedesk`). Override them in `.env` if you change the compose file.

---

### Step 3 — Install PHP dependencies

```bash
docker compose exec php composer install
```

---

### Step 4 — Run database migrations (schema)

Schema is defined in **`application/migrations/`** (and mirrored in `application/migrations/schema.sql` for reference). **Always run migrations before seeds** so tables exist.

With CodeIgniter 3 wired and `public/index.php` as the front controller:

```bash
docker compose exec php php /var/www/html/public/index.php migrate
```

That runs all pending migrations up to `application/config/migration.php` → `migration_version`.

**Notes:**

- `application/config/database.php` must point at the `db` service (hostname **`db`** from inside Docker) with the same database name and user as MySQL in Compose.
- In **production**, do not expose the `Migrate` controller over HTTP; use CLI only (see `application/controllers/Migrate.php`).

---

### Step 5 — Seed the database (data only)

After migrations succeed, load **data** from `database/seeds.sql` (demo users, products, etc.). This file must **not** recreate tables if you already migrated.

```bash
docker compose exec -i db mysql -u stripedesk -pstripedesk stripedesk < database/seeds.sql
```

Adjust user, password, and database name if your `.env` / Compose values differ (`MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DATABASE`).

**Seeded accounts** (from `database/seeds.sql`; password for all: **Password123!**):

| Name        | Email                 | Role  |
|-------------|------------------------|-------|
| Admin User  | admin@stripedesk.local | admin |
| Jane Smith  | jane@stripedesk.local  | user  |
| Bob Johnson | bob@stripedesk.local   | user  |

The file also adds **EUR**, three **products**, sample **orders** / **order_items**, one **invoice** + **receipt**, and a **stripe_logs** row. Re-running the seed uses `ON DUPLICATE KEY UPDATE` on fixed `id` values where applicable.

---

### Step 6 — Verify the API is running

Open your browser:

- **API root**: [http://localhost:8081](http://localhost:8081) (or the port you set in `WEB_PORT`)
- **API docs (hosted)**: [https://api-stripedesk.duolinkmm.com/docs/#/](https://api-stripedesk.duolinkmm.com/docs/#/) — same OpenAPI spec as local [http://localhost:8081/docs/](http://localhost:8081/docs/) when Docker is running
- Optional: add **MailHog** (or another mail catcher) to Compose for local email debugging.

---

## Running Locally with Ngrok & Stripe

Stripe webhooks require a **publicly accessible HTTPS URL**. Ngrok creates a secure tunnel from the internet to your local machine.

### Step 1 — Start the Ngrok tunnel

```bash
ngrok http 8081
```

Ngrok will output something like:

```
Forwarding   https://a1b2-203-0-113-42.ngrok-free.app -> http://localhost:8081
```

Copy the `https://...ngrok-free.app` URL.

---

### Step 2 — Register the webhook on Stripe

1. Go to [https://dashboard.stripe.com/test/webhooks](https://dashboard.stripe.com/test/webhooks)
2. Click **Add endpoint**
3. Set **Endpoint URL** to:
   ```
   https://YOUR-NGROK-SUBDOMAIN.ngrok-free.app/api/v1/stripe/webhook
   ```
4. Under **Events to listen to**, select:
   - `checkout.session.completed`
   - `payment_intent.payment_failed`
5. Click **Add endpoint**
6. Click **Reveal** under **Signing secret** and copy the `whsec_...` value

---

### Step 3 — Update your `.env`

```env
STRIPE_WEBHOOK_SECRET=whsec_REPLACE_WITH_YOUR_SECRET
APP_URL=https://YOUR-NGROK-SUBDOMAIN.ngrok-free.app
```

Restart PHP (and nginx if you changed proxy-related config) to pick up new env vars:

```bash
docker compose restart php nginx
```

---

### Step 4 — Sync products to Stripe

1. Log in as admin → go to **Products**
2. For each product, click the **Sync to Stripe** button
3. This creates a `Price` object in Stripe and stores the `stripe_price_id` in the database —
   required before any checkout can proceed

---

## Stripe Test Mode Walkthrough

### Getting your test keys

1. Sign in at [https://dashboard.stripe.com](https://dashboard.stripe.com)
2. Make sure **Test mode** is toggled ON (top-right toggle)
3. Go to **Developers → API keys**
4. Copy **Publishable key** (`pk_test_...`) and **Secret key** (`sk_test_...`)
5. Paste both into your `.env`

---

### Making a test purchase

1. Log in as a user in the **Vue SPA** (e.g. `jane@stripedesk.local` from seeds)
2. Open the **shop**, add a product, and complete **Stripe Checkout**
3. You are redirected to the **Stripe-hosted Checkout page**
4. Use one of these test cards:

| Scenario          | Card Number           | Expiry      | CVC |
|-------------------|-----------------------|-------------|-----|
| Successful pay    | `4242 4242 4242 4242` | Any future  | Any |
| Card declined     | `4000 0000 0000 0002` | Any future  | Any |
| Requires 3D auth  | `4000 0025 0000 3155` | Any future  | Any |

5. On success → webhook and/or **checkout reconcile** runs the shared fulfillment path → StripeDesk:
   - Marks the order as `paid` (when payment is complete)
   - Creates **invoice** and **receipt** records
   - Exposes receipt **PDF** via `GET /api/v1/receipts/{id}/pdf` (see SPA)
   The SPA redirects to a **success** route; there is **no** post-payment HTML receipt email in this API — only **OTP** mail via `MAIL_*` when SMTP is configured (optional MailHog in dev).

6. On cancel → Stripe redirects to your configured cancel URL (`STRIPE_CANCEL_URL`)

---

### Verifying the webhook locally

After a test purchase, check the Stripe dashboard:

- **Developers → Webhooks → your endpoint → Recent deliveries**
- You should see `checkout.session.completed` with a `200` response

Check StripeDesk application logs:

```bash
docker compose exec php tail -f /var/log/php_errors.log
```

---

## API documentation

All REST routes, request/response schemas, and **Try it out** tooling live in the hosted Swagger UI (OpenAPI spec). This README does not duplicate endpoint documentation.

**[https://api-stripedesk.duolinkmm.com/docs/#/](https://api-stripedesk.duolinkmm.com/docs/#/)**

For local development, run Docker and open `http://localhost:8081/docs/` (same UI; spec source: `public/docs/openapi.yaml`). Optional Postman import: `postman/StripeDesk.postman_collection.json` — set the collection `base_url` to your API origin.

---

## Security & Task Restriction Design

This section describes how StripeDesk ensures each user can only perform tasks they are authorised for via the API.

### 1. Role-based access control (RBAC)

Every protected controller extends `MY_Controller`, which enforces authentication and role checks before any method executes:

```php
// Blocks unauthenticated users — redirects to /login
protected function require_auth() { ... }

// Blocks non-admin users with a 403 — calls require_auth() first
protected function require_admin() { ... }
```

There are two roles — `admin` and `user` — enforced at the controller layer and again at the data layer (users can only query their own records).

### 2. Data ownership enforcement (IDOR prevention)

Even if a user guesses another user's invoice ID, the controller verifies ownership before returning data:

```php
if ($current_user->role !== 'admin' && $invoice->user_id != $current_user->id) {
    show_error('Access denied.', 403);
}
```

This dual-layer check (controller + model query filter) prevents Insecure Direct Object Reference (IDOR) attacks.

### 3. CSRF protection

CodeIgniter's built-in CSRF middleware is active for all HTML form submissions. Every form includes a rotating CSRF token. Webhook and API endpoints are explicitly excluded (they authenticate via Stripe signature and JWT respectively).

### 4. Stripe webhook signature verification

Every incoming webhook request is verified against the `STRIPE_WEBHOOK_SECRET`:

```php
$event = \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_secret);
```

Requests with invalid or missing signatures are rejected with HTTP 400. This prevents spoofed payment confirmation attacks.

### 5. API JWT authentication

All API endpoints require a signed HS256 JWT token in the `Authorization: Bearer` header. Tokens expire after 24 hours. The signing secret is set via `JWT_SECRET` in the environment — never hardcoded in source.

### 6. Recommended further enhancements

| Threat                            | Recommended Control                                      |
|-----------------------------------|----------------------------------------------------------|
| Brute-force login                 | Rate limiting: 5 attempts per 15 min per IP (Nginx `limit_req`) |
| Session hijacking                 | `sess_match_ip = TRUE`, HTTPS-only + HttpOnly cookies    |
| SQL injection                     | CI3 Active Record (parameterised queries) — already in use |
| XSS                               | `htmlspecialchars()` on all user-supplied output         |
| Sensitive data in logs            | Mask card numbers and PII before writing to `stripe_logs` |
| Privilege escalation via API      | Re-fetch role from DB on every request, not from token alone |
| Long-lived token abuse            | Short-lived access tokens (15–60 min) + refresh token flow |

---

## Mobile API Considerations

If StripeDesk were extended to serve a mobile application (iOS/Android), the following points would need to be addressed.

### What already works for mobile

- The `/api/v1/` REST structure and JWT authentication are mobile-ready out of the box
- JSON responses with explicit typed fields work well with mobile JSON parsers
- Role-based access control translates directly to mobile permission models

### What to add or change

**Token refresh flow**
The current 24-hour JWT has no refresh mechanism. Mobile apps require persistent sessions. Implement `POST /api/v1/auth/refresh` to accept a long-lived refresh token and return a new short-lived access token (15–60 min). Store the refresh token securely in the device's Keychain (iOS) or Keystore (Android).

**Versioned endpoints**
The `/api/v1/` prefix is already in place. Maintain this discipline — never make breaking changes to v1. Introduce `/api/v2/` for incompatible changes. Mobile app versions in the wild cannot be forced to update immediately.

**Pagination**
The current API returns full result sets. All list endpoints should support cursor or offset pagination:
```
GET /api/v1/invoices?page=1&per_page=20
```
Response should include `meta.total`, `meta.page`, `meta.last_page`.

**HTTPS enforcement + SSL pinning**
All API traffic must be HTTPS-only. For high-security environments, SSL certificate pinning should be implemented in the mobile client to prevent MITM attacks on public Wi-Fi.

**Push notifications**
For payment confirmations, mobile apps should use push notifications (FCM/APNs) rather than polling. The Stripe webhook handler is the correct place to trigger a push after `checkout.session.completed`.

**Offline resilience**
Mobile apps may be offline when a payment completes. The app should re-fetch invoice/receipt status on app resume via `GET /api/v1/invoices`.

**Native Stripe SDKs**
For a native mobile payment experience, replace the Stripe Checkout redirect with the Stripe native SDKs (`stripe-ios`, `stripe-android`) and use Payment Intents directly. The backend webhook handler remains unchanged.

### Limitations of CodeIgniter 3 for mobile APIs

| Limitation                          | Impact                                           | Mitigation                                       |
|-------------------------------------|--------------------------------------------------|--------------------------------------------------|
| No async / event loop               | Cannot handle WebSockets or server-sent events   | Use Stripe webhooks for real-time event delivery |
| OpenAPI spec is file-driven         | Spec can drift from code if not updated          | Keep `public/docs/openapi.yaml` in sync; publish via [Swagger UI](https://api-stripedesk.duolinkmm.com/docs/#/) |
| No native rate limiting middleware  | Login brute-force not throttled at app level     | Use Nginx `limit_req_zone` or a Redis limiter    |
| Synchronous PHP-FPM                 | High concurrency exhausts the worker pool        | Increase FPM workers; offload email to a queue   |
| CI3 is legacy (PHP 7.x only)        | Cannot use PHP 8 attributes, fibres, or enums    | Consider migrating to CI4 or Laravel for v2      |

---

## Staging vs Production Deployment

### Environment strategy

StripeDesk uses the same Docker images across all environments. The only difference is the `.env` file and which Stripe key set is active (test vs live).

| Setting           | Local dev               | Staging                         | Production                  |
|-------------------|-------------------------|---------------------------------|-----------------------------|
| `CI_ENV`          | `development`           | `testing`                       | `production`                |
| Stripe keys       | `sk_test_...`           | `sk_test_...`                   | `sk_live_...`               |
| `APP_URL`         | `http://localhost:8081` | `https://staging.yourdomain.com`| `https://yourdomain.com`    |
| SMTP              | MailHog (local)         | Mailtrap sandbox                | SendGrid / SES (live)       |
| Database          | Docker MySQL            | RDS db.t3.micro                 | RDS db.t3.small (Multi-AZ)  |
| Error display     | ON                      | OFF                             | OFF                         |

---

### Recommended AWS cloud architecture

```
                        ┌─────────────────────┐
                        │    Route 53 (DNS)   │
                        └──────────┬──────────┘
                                   │
                        ┌──────────▼──────────┐
                        │  ACM (TLS cert)     │
                        └──────────┬──────────┘
                                   │ HTTPS 443
                        ┌──────────▼──────────┐
                        │  Application Load   │
                        │  Balancer (ALB)     │
                        └────────┬─────┬──────┘
                                 │     │
               ┌─────────────────▼─┐ ┌─▼─────────────────┐
               │  ECS Fargate Task │ │ ECS Fargate Task   │
               │  (Nginx + PHP)    │ │ (Nginx + PHP)      │
               │  AZ ap-southeast-1a│ │ AZ ap-southeast-1b│
               └─────────────────┬─┘ └─┬─────────────────┘
                                 │     │
               ┌─────────────────▼─────▼─────────────────┐
               │         Amazon RDS MySQL 8               │
               │  Staging: db.t3.micro, Single-AZ         │
               │  Production: db.t3.small, Multi-AZ       │
               └──────────────────────────────────────────┘

Supporting services:
  ECR             → Docker image registry (tagged by git SHA)
  S3              → Static assets, future PDF invoice storage
  SES / SendGrid  → Transactional email (receipts)
  CloudWatch      → Logs, metrics, alarms
  Secrets Manager → Stripe keys, DB passwords, JWT secret
```

---

### Deployment strategy

**Staging (automatic on push to `develop`):**
```
git push origin develop
  → GitHub Actions: build Docker image
  → push to ECR with tag :staging-<sha>
  → ECS update-service (rolling deploy)
  → smoke tests (curl health check)
```

**Production (manual approval on `main`):**
```
PR merged to main
  → GitHub Actions: build Docker image
  → push to ECR with tag :latest + :<sha>
  → Manual approval gate in GitHub Actions
  → ECS blue/green deploy via CodeDeploy
  → ALB shifts traffic: 10% → 50% → 100% over 10 min
  → Old task set drained and terminated
```

Database migrations run as a one-off ECS task before each traffic shift, ensuring schema is updated before new code handles requests.

---

### Estimated monthly AWS cost (ap-southeast-1 region)

| Service                        | Spec                        | Est. Cost (USD/mo) |
|-------------------------------|-----------------------------|--------------------|
| ECS Fargate (2 tasks)         | 0.5 vCPU, 1 GB RAM each     | ~$15               |
| RDS MySQL db.t3.micro         | Single-AZ — staging         | ~$15               |
| RDS MySQL db.t3.small         | Multi-AZ — production       | ~$60               |
| Application Load Balancer     | 1 ALB                       | ~$20               |
| ECR                           | 5 GB image storage          | ~$0.50             |
| SES                           | 10,000 emails/mo            | ~$1                |
| CloudWatch Logs               | 5 GB/mo                     | ~$2.50             |
| Route 53                      | 1 hosted zone               | ~$0.50             |
| **Total — staging only**      |                             | **~$55/mo**        |
| **Total — staging + prod**    |                             | **~$115/mo**       |

> Costs reduce significantly with Fargate Savings Plans (up to 50% off compute) and RDS Reserved Instances (up to 40% off).

---

## Additional Tools & Libraries

| Tool / Library              | Purpose                                                       |
|-----------------------------|---------------------------------------------------------------|
| **Stripe PHP SDK ^7**       | Checkout Sessions, Webhook verification, Price/Product API    |
| **Firebase PHP-JWT ^5.5**   | HS256 JWT encode/decode for the REST API                      |
| **Dompdf ^2**               | HTML → PDF for downloadable receipts                            |
| **PHPMailer v6**            | SMTP for OTP / verification email (`Stripedesk\Mail\Otp_mailer`) |
| **MailHog**                 | Optional local SMTP capture (add to Compose if needed)        |
| **Ngrok v3**                | Secure HTTPS tunnel for Stripe webhook testing locally        |
| **Docker + Compose v2**     | Full environment containerisation and service orchestration   |
| **MySQL 8**                 | JSON column type used for `stripe_logs.payload`               |
| **Nginx 1.25 (Alpine)**     | Lightweight web server proxying to PHP-FPM                    |
| **Composer**                | PHP dependency management                                     |
| **Postman**                 | API development and exportable collection for team sharing    |

### Recommended additions for production

| Tool                   | Purpose                                                          |
|------------------------|------------------------------------------------------------------|
| **Redis**              | Session storage (replace DB sessions), API rate limiting         |
| **Sentry**             | Real-time error tracking and performance monitoring              |
| **PHPUnit**            | Unit and integration tests for models, libraries, and webhooks   |
| **GitHub Actions**     | CI/CD: test → build → push ECR → deploy ECS                     |
| **AWS Secrets Manager**| Secure storage of Stripe keys, JWT secret, and DB credentials    |
| **Datadog / CloudWatch**| APM, log aggregation, uptime alarms                             |

---

## Email (SMTP for OTP)

Outbound mail is used for **OTP / verification** messages (`application/Stripedesk/Mail/Otp_mailer.php`, invoked from `Auth_password_service`). Plain-text body is built in code, not a separate view file.

**Receipts** are exposed over the API as data and **PDF download** (`Receipt_html_document_builder` + Dompdf), not as a post-payment HTML email in this repository.

See [OTP verification & SMTP (production note)](#otp-verification--smtp-production-note) if outbound mail is blocked on your host.

### Local development (MailHog)

If you add **MailHog** (or another mail catcher) to Compose, point the `MAIL_*` variables below at it. The default `docker-compose.yml` does **not** include MailHog.

### Configuring SMTP (matches `.env.example`)

```env
# Mailtrap (staging — inbox capture, not real recipients)
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=StripeDesk

# SendGrid (example — use their SMTP credentials)
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=SG.your_sendgrid_api_key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=receipts@yourdomain.com
MAIL_FROM_NAME=StripeDesk
```

---

## Project Structure

High level: **REST API + JSON** (no server-rendered admin/shop). The Vue SPA lives in **StripeDesk_FrontEnd**.

```
stripedesk/
├── Dockerfile                    # PHP-FPM app image (Composer deps)
├── docker-compose.yml            # nginx + php + db
├── nginx/
│   ├── Dockerfile
│   ├── nginx.conf
│   └── sites/stripedesk.conf     # try_files → public/index.php
├── application/
│   ├── config/                   # config, database, routes, jwt, stripe, env, migration, autoload
│   ├── controllers/
│   │   ├── Migrate.php           # migrations (prefer CLI in prod)
│   │   ├── Cron.php              # e.g. stripe_reconcile
│   │   ├── Welcome.php
│   │   └── api/
│   │       ├── Auth.php
│   │       ├── Products.php
│   │       ├── Checkout.php      # session create + reconcile
│   │       ├── Stripe.php        # POST …/stripe/webhook
│   │       ├── Carts.php
│   │       ├── Invoices.php
│   │       ├── Receipts.php      # list + PDF
│   │       └── admin/
│   │           ├── Users.php
│   │           ├── Products.php
│   │           ├── Currencies.php
│   │           └── Stripe_logs.php
│   ├── core/
│   │   ├── MY_Controller.php
│   │   ├── MY_Model.php
│   │   └── Api_base_controller.php
│   ├── migrations/               # canonical schema (+ schema.sql mirror)
│   ├── models/                   # User, Product, Order, Invoice, Receipt, Cart, OTP, …
│   ├── libraries/
│   │   └── Jwt_auth.php
│   ├── Stripedesk/               # namespaced app layer
│   │   ├── Services/             # checkout, webhook, fulfillment, receipt PDF, auth, …
│   │   ├── Dto/
│   │   ├── Contracts/            # e.g. Pdf_renderer_interface
│   │   ├── Mail/Otp_mailer.php
│   │   ├── Api/                  # standardized JSON responses
│   │   ├── Support/
│   │   └── Validation/
│   └── views/
│       └── errors/               # CI error templates only
├── database/
│   └── seeds.sql
├── postman/
│   └── StripeDesk.postman_collection.json
├── public/
│   ├── index.php                 # front controller + .env load
│   └── docs/
│       └── openapi.yaml          # Swagger UI source
├── composer.json
├── composer.lock
├── .env.example
└── README.md
```

---

## Quick Reference

```bash
# Start all containers
docker compose up -d --build

# Stop all containers
docker compose down

# View all container logs
docker compose logs -f

# View PHP error log
docker compose exec php tail -f /var/log/php_errors.log

# Access MySQL shell (defaults: user/db stripedesk — match your .env)
docker compose exec db mysql -u stripedesk -pstripedesk stripedesk

# Install / update Composer dependencies
docker compose exec php composer install

# Run migrations (schema)
docker compose exec php php /var/www/html/public/index.php migrate

# Seed data (after migrations)
docker compose exec -i db mysql -u stripedesk -pstripedesk stripedesk < database/seeds.sql

# Restart PHP (and nginx if needed) after .env changes
docker compose restart php nginx

# API docs (hosted); use http://localhost:8081/docs/ when running Docker locally
open https://api-stripedesk.duolinkmm.com/docs/#/

# Open the API root in browser (macOS)
open http://localhost:8081

# MailHog (only if you add it to docker-compose)
# open http://localhost:8025

# Start Ngrok tunnel for Stripe webhooks
ngrok http 8081
```

---

*StripeDesk — built with CodeIgniter 3, PHP 7.3, MySQL 8, and Stripe.*
