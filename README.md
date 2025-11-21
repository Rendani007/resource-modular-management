Why this project:
Multi-tenant by design – every request is scoped to a tenant (company).
Real inventory flows – items, locations, stock in/out/transfer with negative-stock protection.
Production patterns – request validation, idempotency keys, throttle, clean error shape.

PHP 8.3+, Composer
PostgreSQL 14+
(Optional) Node for the separate frontend


Install
git clone your repo url
cd rmm-api
cp .env.example .env
composer install
php artisan key:generate

Authentication:
Login: POST /api/v1/auth/login
Body: { "email": "...", "password": "...", "tenant_slug": "..." }

Please use the returned Bearer token for protected endpoints:

Profile: GET /api/v1/auth/profile
Logout: POST /api/v1/auth/logout
Change password: POST /api/v1/auth/change-password
Register user (tenant admin only): POST /api/v1/auth/register

Tenancy:
A middleware attaches app('tenant_id') from the authenticated user.
All queries are automatically filtered by tenant_id.

API surface

Inventory Items

GET /api/v1/inventory/items (paginated)
POST /api/v1/inventory/items (admin)
GET /api/v1/inventory/items/{id}
PUT /api/v1/inventory/items/{id} (admin)
DELETE /api/v1/inventory/items/{id} (admin)
GET /api/v1/inventory/items/{id}/stock → computed stock per location

Locations

GET /api/v1/inventory/locations
POST /api/v1/inventory/locations (admin)
GET /api/v1/inventory/locations/{id}
PUT /api/v1/inventory/locations/{id} (admin)
DELETE /api/v1/inventory/locations/{id} (admin)

Stock Movements

POST /api/v1/inventory/stock/in
POST /api/v1/inventory/stock/out
POST /api/v1/inventory/stock/transfer

On out/transfer, the server validates there is sufficient stock at the source location (no negatives).

HTTP codes used: 200/201, 401/403, 404, 409, 422, 429, 500.

Idempotency

Stock endpoints accept:
Idempotency-Key: <uuid>
to avoid duplicate writes on retries.


Rate limiting & CORS

throttle:api globally; extra throttling on login.
CORS is configured for SPA usage with Bearer tokens (no cookies).
Allow your frontend origin (e.g., http://localhost:8080).


Demo flow (happy path)

GET /api/v1/dev/create-demo-tenant
POST /api/v1/auth/login
POST /api/v1/inventory/locations
POST /api/v1/inventory/items
POST /api/v1/inventory/stock/in
GET /api/v1/inventory/items/{id}/stock


Troubleshooting:

401 Unauthenticated → Missing/expired token; login again and include Authorization: Bearer <token>.

CORS in browser → Make sure your frontend origin is allowed and you’re using Bearer tokens.

Tokens migration error → personal_access_tokens.id must be UUID; the project includes the correct migration + config/sanctum.php model override.

Empty lists → Ensure the data’s tenant_id matches the logged-in user’s tenant.
