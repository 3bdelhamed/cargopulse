# CargoPulse API v1 — Complete Endpoint Documentation

> **Base URL:** `{{base_url}}/api/v1`  
> **Authentication:** Laravel Sanctum (Bearer token)  
> **Content-Type:** `application/json`  
> **Tenancy:** Every operational endpoint is automatically scoped to the authenticated user's `tenant_id` via Laravel Global Scope (`TenantScope`). Super Admin endpoints bypass this scope.

---

## Table of Contents

1. [Authentication & Onboarding](#1-authentication--onboarding)
2. [Super Admin (Platform Level)](#2-super-admin-platform-level)
3. [Tenant Settings & Billing](#3-tenant-settings--billing)
4. [Merchant Management & API](#4-merchant-management--api)
5. [Shipments](#5-shipments)
6. [Warehouses & Inventory](#6-warehouses--inventory)
7. [Routes & Fleet Management](#7-routes--fleet-management)
8. [Driver Mobile Operations](#8-driver-mobile-operations)
9. [Public Tracking](#9-public-tracking)
10. [Analytics & Dashboard](#10-analytics--dashboard)
11. [Event-Driven Workflows](#11-event-driven-workflows)
12. [Error Reference](#12-error-reference)

---

## 1. Authentication & Onboarding

### `POST /auth/login`
Authenticate a User, Merchant, or Driver and receive a Sanctum access token with contextual abilities.

**Authentication:** None (public)

**Request Body:**
```json
{
  "email": "admin@cairo-express.test",
  "password": "password",
  "type": "user"        // enum: user | merchant (default: user)
}
```

**Validation Rules:**
- `email` — required, valid email format
- `password` — required, string
- `type` — nullable, `in:user,merchant`

**Business Logic:**
1. If `type === "merchant"`, authenticates against `Merchant::where('contact_email', ...)` and issues a token with abilities `["shipments:create", "shipments:read"]`.
2. If `type === "user"` (or omitted), authenticates against `User::where('email', ...)` via `Hash::check()`.
3. User tokens receive contextual abilities based on `role`:
   - `Company Admin` → `["*"]` (full tenant access)
   - `Warehouse Manager` → `["shipments:manage", "warehouse:manage"]`
   - `Driver` → `["shipments:update", "gps:update"]`
   - default → `["read-only"]`

**Success Response (200):**
```json
{
  "access_token": "1|laravel_sanctum_token_hash...",
  "token_type": "Bearer",
  "user": { "id": 2, "name": "Mona Hassan", "email": "admin@cairo-express.test", "tenant_id": 1, "role": "Company Admin" }
}
```

**Error Responses:**
- `422` — ValidationException (invalid email format, missing fields)
- `401` — `ValidationException` with message *"The provided credentials do not match our records."*

**Postman Script:**
```javascript
var j = pm.response.json();
pm.collectionVariables.set('tenant_admin_token', j.access_token);
pm.collectionVariables.set('current_tenant_id', j.user.tenant_id);
pm.collectionVariables.set('current_user_id', j.user.id);
```

---

### `POST /auth/logout`
Revoke the current Sanctum access token.

**Authentication:** `Bearer {token}` (auth:sanctum)

**Request Body:** None

**Success Response (200):**
```json
{ "message": "Successfully logged out" }
```

---

### `GET /user`
Return the currently authenticated user with tenant scoping.

**Authentication:** `Bearer {token}` (auth:sanctum)

**Success Response (200):**
```json
{
  "id": 2,
  "name": "Mona Hassan",
  "email": "admin@cairo-express.test",
  "tenant_id": 1,
  "role": "Company Admin",
  "created_at": "2024-01-15T08:00:00.000000Z"
}
```

---

### `POST /auth/merchant-keys`
Generate a new API key for a B2B merchant.

**Authentication:** `Bearer {tenant_admin_token}` (auth:sanctum)

**Request Body:**
```json
{
  "merchant_id": 1,
  "name": "Production E-commerce Integration"
}
```

**Success Response (201):**
```json
{
  "message": "API key generated successfully",
  "data": {
    "id": 1,
    "merchant_id": 1,
    "name": "Production E-commerce Integration",
    "api_key": "cp_live_xxxxxxxxxxxxxxxx",
    "created_at": "2024-02-15T10:00:00.000000Z"
  }
}
```

---

## 2. Super Admin (Platform Level)

> These endpoints bypass `TenantScope`. Only accessible by users with the `Super Admin` role.

### `POST /platform/tenants`
Onboard a new logistics company (tenant) onto the CargoPulse SaaS platform.

**Authentication:** `Bearer {super_admin_token}`

**Request Body:**
```json
{
  "name": "Alexandria Fast Cargo",
  "domain": "alex-fast.test",
  "contact_email": "ops@alex-fast.test",
  "billing_email": "billing@alex-fast.test",
  "plan": "professional",
  "max_drivers": 50,
  "max_warehouses": 10
}
```

**Validation Rules:**
- `name` — required, string, max 255
- `domain` — required, unique, string
- `contact_email` — required, email
- `billing_email` — required, email
- `plan` — required, `in:starter,professional,enterprise`
- `max_drivers` — required, integer, min 1
- `max_warehouses` — required, integer, min 1

**Success Response (201):**
```json
{
  "message": "Tenant created successfully",
  "data": {
    "id": 3,
    "name": "Alexandria Fast Cargo",
    "domain": "alex-fast.test",
    "status": "active",
    "plan": "professional",
    "max_drivers": 50,
    "max_warehouses": 10,
    "created_at": "2024-02-15T10:00:00.000000Z"
  }
}
```

**Side Effects:**
- Creates tenant record
- Initializes default subscription billing record
- Sets up Stripe customer object (if Stripe key configured)

---

### `GET /platform/tenants`
List all tenants on the platform.

**Authentication:** `Bearer {super_admin_token}`

**Success Response (200):**
```json
{
  "message": "Tenants retrieved successfully",
  "data": [
    { "id": 1, "name": "Cairo Express Logistics", "domain": "cairo-express.test", "status": "active", "plan": "professional" },
    { "id": 2, "name": "Delta Freight Co", "domain": "delta-freight.test", "status": "active", "plan": "starter" }
  ],
  "meta": { "current_page": 1, "per_page": 25, "total": 2 }
}
```

---

### `GET /platform/tenants/{id}`
Get detailed information for a specific tenant.

**Authentication:** `Bearer {super_admin_token}`

**Path Parameters:**
- `id` — Tenant ID (integer)

**Success Response (200):**
```json
{
  "message": "Tenant retrieved successfully",
  "data": {
    "id": 1,
    "name": "Cairo Express Logistics",
    "domain": "cairo-express.test",
    "status": "active",
    "plan": "professional",
    "subscription": { "stripe_customer_id": "cus_xxx", "current_period_end": "2024-12-31" },
    "stats": { "total_shipments": 1250, "active_drivers": 12, "active_merchants": 8 }
  }
}
```

---

### `GET /platform/health`
System health, queue status, and Horizon metrics.

**Authentication:** `Bearer {super_admin_token}`

**Success Response (200):**
```json
{
  "message": "System health retrieved",
  "data": {
    "status": "healthy",
    "queue_size": 12,
    "failed_jobs": 0,
    "redis_connected": true,
    "database_connected": true,
    "horizon_status": "running",
    "php_version": "8.3.2",
    "laravel_version": "11.x"
  }
}
```

---

## 3. Tenant Settings & Billing

### `GET /tenant/subscription`
View current SaaS subscription, usage limits, and consumption.

**Authentication:** `Bearer {tenant_admin_token}`

**Success Response (200):**
```json
{
  "message": "Subscription retrieved successfully",
  "data": {
    "plan": "professional",
    "billing_cycle": "monthly",
    "max_drivers": 50,
    "max_warehouses": 10,
    "max_merchants": 100,
    "current_drivers_count": 12,
    "current_warehouses_count": 2,
    "current_merchants_count": 8,
    "features": ["realtime_tracking", "api_access", "bulk_import", "webhooks"],
    "stripe_subscription_id": "sub_xxx",
    "current_period_end": "2024-03-15"
  }
}
```

---

### `POST /tenant/billing/upgrade`
Upgrade or downgrade the tenant's SaaS subscription plan via Stripe.

**Authentication:** `Bearer {tenant_admin_token}`

**Request Body:**
```json
{
  "plan": "enterprise",
  "payment_method_id": "pm_1234567890",
  "billing_cycle": "annual"
}
```

**Validation Rules:**
- `plan` — required, `in:starter,professional,enterprise`
- `payment_method_id` — required, string (Stripe PaymentMethod ID)
- `billing_cycle` — required, `in:monthly,annual`

**Success Response (200):**
```json
{
  "message": "Subscription upgraded successfully",
  "data": {
    "plan": "enterprise",
    "billing_cycle": "annual",
    "max_drivers": 200,
    "max_warehouses": 50,
    "stripe_subscription_id": "sub_new_xxx",
    "current_period_end": "2025-02-15"
  }
}
```

**Error Responses:**
- `402` — Payment required (Stripe card declined)
- `422` — Invalid plan or billing cycle

---

## 4. Merchant Management & API

### `POST /merchants`
Create a new B2B merchant client under the current tenant.

**Authentication:** `Bearer {tenant_admin_token}`

**Request Body:**
```json
{
  "name": "Nile Store",
  "contact_email": "merchant@nile-store.test",
  "billing_email": "billing@nile-store.test",
  "phone": "+201000000001",
  "address": "123 Commerce St, Cairo",
  "webhook_url": "https://nile-store.test/webhooks/cargopulse",
  "api_rate_limit": 1000
}
```

**Validation Rules:**
- `name` — required, string, max 255
- `contact_email` — required, email, unique per tenant
- `billing_email` — required, email
- `phone` — required, string
- `address` — nullable, string
- `webhook_url` — nullable, url
- `api_rate_limit` — nullable, integer, min 10, max 10000

**Success Response (201):**
```json
{
  "message": "Merchant created successfully",
  "data": {
    "id": 1,
    "tenant_id": 1,
    "name": "Nile Store",
    "contact_email": "merchant@nile-store.test",
    "api_key": "cp_merch_live_xxxxxxxx",
    "webhook_url": "https://nile-store.test/webhooks/cargopulse",
    "created_at": "2024-01-15T08:00:00.000000Z"
  }
}
```

**Side Effects:**
- Auto-generates API key for B2B integration
- Creates initial ledger balance of 0

---

### `GET /merchants`
List all merchants belonging to the current tenant.

**Authentication:** `Bearer {tenant_admin_token}`

**Success Response (200):**
```json
{
  "message": "Merchants retrieved successfully",
  "data": [
    { "id": 1, "name": "Nile Store", "contact_email": "merchant@nile-store.test", "active": true, "total_shipments": 450 },
    { "id": 2, "name": "Market Kart", "contact_email": "merchant@market-kart.test", "active": true, "total_shipments": 230 }
  ]
}
```

---

### `GET /merchants/{id}`
Get detailed merchant profile.

**Authentication:** `Bearer {tenant_admin_token}`

**Path Parameters:**
- `id` — Merchant ID (integer)

**Success Response (200):**
```json
{
  "message": "Merchant retrieved successfully",
  "data": {
    "id": 1,
    "name": "Nile Store",
    "contact_email": "merchant@nile-store.test",
    "ledger": { "balance": 1250.50, "currency": "EGP" },
    "webhooks": [{ "url": "https://nile-store.test/webhooks/cargopulse", "events": ["shipment.delivered"], "active": true }],
    "api_usage": { "requests_this_month": 8420, "rate_limit": 10000 }
  }
}
```

**Error Responses:**
- `404` — Merchant not found (or belongs to another tenant — hidden by TenantScope)

---

### `POST /merchants/{id}/webhooks`
Configure or update webhook endpoints for a merchant.

**Authentication:** `Bearer {tenant_admin_token}`

**Path Parameters:**
- `id` — Merchant ID

**Request Body:**
```json
{
  "url": "https://nile-store.test/webhooks/cargopulse",
  "events": ["shipment.created", "shipment.delivered", "shipment.failed"],
  "secret": "whsec_test_secret_123",
  "active": true
}
```

**Validation Rules:**
- `url` — required, valid URL, max 2048
- `events` — required, array of strings
- `secret` — required, string, min 16
- `active` — boolean

**Success Response (200):**
```json
{
  "message": "Webhook configured successfully",
  "data": {
    "merchant_id": 1,
    "webhook_url": "https://nile-store.test/webhooks/cargopulse",
    "events": ["shipment.created", "shipment.delivered", "shipment.failed"],
    "active": true,
    "updated_at": "2024-02-15T10:00:00.000000Z"
  }
}
```

**Side Effects:**
- Dispatches `WebhookConfiguredEvent`
- Sends a test ping to the URL to verify connectivity

---

### `GET /merchants/{id}/ledger`
View the merchant's Cash on Delivery (COD) and delivery fee ledger.

**Authentication:** `Bearer {tenant_admin_token}`

**Path Parameters:**
- `id` — Merchant ID

**Success Response (200):**
```json
{
  "message": "Ledger retrieved successfully",
  "data": {
    "merchant_id": 1,
    "balance": 1250.50,
    "currency": "EGP",
    "total_cod_collected": 45000.00,
    "total_fees_deducted": -43750.00,
    "transactions": [
      {
        "id": 1,
        "type": "cod_collection",
        "shipment_id": 5,
        "amount": 300.00,
        "description": "COD collected for shipment CP-CAI-005",
        "created_at": "2024-01-15T14:30:00.000000Z"
      },
      {
        "id": 2,
        "type": "delivery_fee",
        "shipment_id": 5,
        "amount": -10.00,
        "description": "Delivery fee for shipment CP-CAI-005",
        "created_at": "2024-01-15T14:30:00.000000Z"
      }
    ]
  }
}
```

---

### `POST /merchants/{id}/invoices/generate`
Trigger invoice generation for a merchant. Queues `GenerateMerchantInvoicesJob` via Horizon.

**Authentication:** `Bearer {tenant_admin_token}`

**Path Parameters:**
- `id` — Merchant ID

**Request Body:**
```json
{
  "period_from": "2024-01-01",
  "period_to": "2024-01-31",
  "format": "pdf"
}
```

**Validation Rules:**
- `period_from` — required, date
- `period_to` — required, date, after or equal to `period_from`
- `format` — required, `in:pdf,json`

**Success Response (202):**
```json
{
  "message": "Invoice generation queued",
  "data": {
    "job_id": "horizon_job_uuid",
    "invoice_id": 15,
    "status": "processing",
    "period": "2024-01-01 to 2024-01-31",
    "estimated_completion": "2024-02-15T10:05:00.000000Z"
  }
}
```

**Side Effects:**
- Queues background job to aggregate all delivered shipments in period
- Calculates delivery fees and COD reconciliation
- Generates unified PDF and emails to merchant billing contact
- Creates `Invoice` and `LedgerTransaction` records

---

## 5. Shipments

### `POST /shipments`
Create a single shipment. Can be called by Tenant Admin or Merchant API key.

**Authentication:** `Bearer {tenant_admin_token}` or `Bearer {merchant_api_key}`

**Request Body (Admin):**
```json
{
  "merchant_id": 1,
  "tracking_number": "CP-CAI-007",
  "reference_number": "ORDER-12345",
  "recipient_name": "John Doe",
  "recipient_phone": "+1-555-0199",
  "recipient_email": "john@example.com",
  "destination_address": "456 Receiver Ave, Los Angeles, CA",
  "destination_lat": 34.0522,
  "destination_lng": -118.2437,
  "weight_kg": 2.5,
  "dimensions": { "length": 30, "width": 20, "height": 15 },
  "cod_amount": 50.00,
  "delivery_notes": "Leave at front door",
  "service_type": "standard",
  "metadata": { "source": "api", "integration": "shopify" }
}
```

**Request Body (Merchant — merchant_id inferred from token):**
```json
{
  "tracking_number": "CP-MERCH-001",
  "reference_number": "ORDER-M-999",
  "recipient_name": "Jane Smith",
  "recipient_phone": "+1-555-0188",
  "destination_address": "456 Commerce Blvd",
  "destination_lat": 30.0626,
  "destination_lng": 31.2497,
  "weight_kg": 1.2,
  "cod_amount": 0,
  "service_type": "express"
}
```

**Validation Rules:**
- `merchant_id` — required for admin, nullable for merchant (auto-resolved)
- `tracking_number` — required, unique per tenant, string, max 255
- `reference_number` — nullable, string
- `recipient_name` — required, string, max 255
- `recipient_phone` — required, string
- `recipient_email` — nullable, email
- `destination_address` — required, string
- `destination_lat` — nullable, numeric
- `destination_lng` — nullable, numeric
- `weight_kg` — nullable, numeric, min 0.01
- `dimensions` — nullable, object
- `cod_amount` — nullable, numeric, min 0
- `service_type` — required, `in:standard,express,same_day`
- `metadata` — nullable, object/JSON

**Success Response (201):**
```json
{
  "message": "Shipment created successfully",
  "data": {
    "id": 7,
    "tenant_id": 1,
    "merchant_id": 1,
    "tracking_number": "CP-CAI-007",
    "reference_number": "ORDER-12345",
    "state": "pending",
    "recipient_name": "John Doe",
    "recipient_phone": "+1-555-0199",
    "destination_address": "456 Receiver Ave, Los Angeles, CA",
    "destination_lat": 34.0522,
    "destination_lng": -118.2437,
    "weight_kg": 2.5,
    "cod_amount": 50.00,
    "service_type": "standard",
    "created_at": "2024-02-15T10:00:00.000000Z"
  }
}
```

**Side Effects:**
- State initialized to `PendingState` via `spatie/laravel-model-states`
- Creates initial `StatusLog` entry
- Fires `ShipmentCreatedEvent` → queues webhook to merchant

---

### `POST /shipments/bulk-import`
Bulk create shipments via CSV upload. Processed by `BulkImportShipmentsAction`.

**Authentication:** `Bearer {tenant_admin_token}`

**Request Body (multipart/form-data):**
```
csv_file: [File upload]
merchant_id: 1
skip_validation_errors: false
```

**CSV Format (headers):**
```
tracking_number,reference_number,recipient_name,recipient_phone,destination_address,destination_lat,destination_lng,weight_kg,cod_amount,service_type
```

**Success Response (200/207 Multi-Status):**
```json
{
  "message": "Bulk import processed",
  "data": {
    "processed_count": 500,
    "failed_count": 5,
    "total_rows": 505,
    "errors": [
      { "row": 47, "tracking_number": "CP-BAD-001", "error": "Missing destination_address" },
      { "row": 203, "tracking_number": "CP-BAD-002", "error": "Invalid cod_amount: -10" }
    ]
  }
}
```

**Business Logic:**
- Action class processes rows in chunks of 100
- Valid rows create shipments within a database transaction
- Invalid rows are collected and returned in error report
- No partial commits — each chunk is atomic

---

### `GET /shipments/search`
Advanced filtering and search with strict database-level pagination.

**Authentication:** `Bearer {tenant_admin_token}`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `state` | string | No | Filter by shipment state |
| `merchant_id` | integer | No | Filter by merchant |
| `tracking_number` | string | No | Partial or exact match |
| `date_from` | date | No | Created after |
| `date_to` | date | No | Created before (≥ date_from) |
| `sort_by` | string | No | Column name |
| `sort_direction` | string | No | `asc` or `desc` |
| `per_page` | integer | No | 1–100 (default 25) |

**Success Response (200):**
```json
{
  "message": "Shipments retrieved successfully",
  "data": {
    "current_page": 1,
    "data": [ /* Shipment objects */ ],
    "first_page_url": "...",
    "from": 1,
    "last_page": 5,
    "next_page_url": "...",
    "per_page": 25,
    "prev_page_url": null,
    "to": 25,
    "total": 125
  }
}
```

---

### `GET /shipments/{id}`
Retrieve a single shipment with eager-loaded relations.

**Authentication:** `Bearer {tenant_admin_token}`

**Path Parameters:**
- `id` — Shipment ID

**Success Response (200):**
```json
{
  "message": "Shipment retrieved successfully",
  "data": {
    "id": 5,
    "tracking_number": "CP-CAI-005",
    "state": "delivered",
    "merchant": { "id": 1, "name": "Nile Store" },
    "driver": { "id": 1, "name": "Youssef Ali", "vehicle_plate": "CP-001" },
    "route": { "id": 1, "name": "Morning Manifest", "date": "2024-02-15" },
    "warehouse": { "id": 1, "name": "Nasr City Hub", "code": "CAI-NC" },
    "status_logs": [
      { "from": "pending", "to": "confirmed", "changed_at": "2024-01-10T08:00:00Z", "changed_by": 2 },
      { "from": "confirmed", "to": "packed", "changed_at": "2024-01-10T09:00:00Z", "changed_by": 3 },
      { "from": "in_transit", "to": "delivered", "changed_at": "2024-01-10T14:30:00Z", "changed_by": 4 }
    ],
    "invoice": { "id": 1, "invoice_number": "INV-DEMO-1", "status": "unpaid" }
  }
}
```

**Error Responses:**
- `404` — Shipment not found within tenant scope (TenantScope enforced)

---

### `PATCH /shipments/{id}/state`
Transition a shipment to a new state via `spatie/laravel-model-states`.

**Authentication:** `Bearer {tenant_admin_token}` or `Bearer {driver_mobile_token}` (for driver-initiated transitions)

**Path Parameters:**
- `id` — Shipment ID

**Request Body:**
```json
{
  "state": "confirmed",
  "reason": "Inventory verified and ready for packing",
  "route_id": null,
  "driver_id": null
}
```

**Valid State Transitions:**
```
Pending → Confirmed → Packed → Assigned → Picked_Up → In_Transit → Delivered
                                    ↓
                                 Failed
```

**Validation Rules:**
- `state` — required, must be a valid registered state class
- `reason` — nullable, string
- `route_id` — required only for `assigned` state, must belong to tenant
- `driver_id` — required only for `assigned` state, must belong to tenant

**Success Response (200):**
```json
{
  "message": "State transitioned successfully",
  "data": {
    "id": 5,
    "tracking_number": "CP-CAI-005",
    "state": "confirmed",
    "previous_state": "pending",
    "transitioned_at": "2024-02-15T10:05:00.000000Z",
    "reason": "Inventory verified and ready for packing"
  }
}
```

**Error Responses:**
- `422` — `TransitionNotAllowed` exception (illegal state jump)
- `404` — Shipment not found
- `403` — User lacks `shipments:manage` or `shipments:update` ability

**Side Effects:**
- Records entry in `StatusLogs`
- Fires domain events (e.g., `ShipmentDeliveredEvent`)
- Triggers async listeners: `SendDeliveryWebhookToMerchant`, `UpdateDriverMetrics`, `PushDeliveryNotificationToCustomer`

---

## 6. Warehouses & Inventory

### `POST /warehouses`
Create a new warehouse or staging hub.

**Authentication:** `Bearer {tenant_admin_token}`

**Request Body:**
```json
{
  "name": "Nasr City Hub",
  "code": "CAI-NC",
  "address": "Nasr City, Cairo",
  "lat": 30.0561,
  "lng": 31.2394,
  "manager_id": 2,
  "capacity": 5000,
  "active": true
}
```

**Validation Rules:**
- `name` — required, string, max 255
- `code` — required, unique per tenant, string, max 50
- `address` — required, string
- `lat` / `lng` — nullable, numeric
- `manager_id` — nullable, exists:users,id (within tenant)
- `capacity` — nullable, integer
- `active` — boolean

**Success Response (201):**
```json
{
  "message": "Warehouse created successfully",
  "data": {
    "id": 1,
    "tenant_id": 1,
    "name": "Nasr City Hub",
    "code": "CAI-NC",
    "location": "Nasr City, Cairo",
    "active": true,
    "created_at": "2024-01-15T08:00:00.000000Z"
  }
}
```

---

### `GET /warehouses`
List all warehouses for the current tenant.

**Authentication:** `Bearer {tenant_admin_token}`

**Success Response (200):**
```json
{
  "message": "Warehouses retrieved successfully",
  "data": [
    { "id": 1, "name": "Nasr City Hub", "code": "CAI-NC", "location": "Nasr City, Cairo", "active": true },
    { "id": 2, "name": "6th October Hub", "code": "CAI-OCT", "location": "6th October City", "active": true }
  ]
}
```

---

### `POST /warehouses/check-in`
Receive a shipment into a warehouse. Highly optimized scanning endpoint.

**Authentication:** `Bearer {tenant_admin_token}` or `Bearer {warehouse_manager_token}`

**Request Body (ScanData DTO):**
```json
{
  "shipment_id": 2,
  "warehouse_id": 1,
  "scanned_by": 3,
  "scan_type": "check_in",
  "barcode": "CP-CAI-002",
  "notes": "Received in good condition"
}
```

**Validation Rules:**
- `shipment_id` — required, exists:shipments,id (tenant-scoped)
- `warehouse_id` — required, exists:warehouses,id (tenant-scoped)
- `scanned_by` — required, exists:users,id
- `scan_type` — required, `in:check_in,transfer,return`
- `barcode` — required, string
- `notes` — nullable, string

**Success Response (200):**
```json
{
  "message": "Shipment checked into warehouse successfully",
  "data": {
    "id": 2,
    "tracking_number": "CP-CAI-002",
    "state": "packed",
    "warehouse_id": 1,
    "checked_in_at": "2024-02-15T09:00:00.000000Z"
  }
}
```

**Side Effects:**
- Transitions shipment state to `PackedState`
- Updates `warehouse_id` on shipment
- Records scan audit log

---

### `POST /warehouses/transfer`
Internal warehouse-to-warehouse transfer.

**Authentication:** `Bearer {tenant_admin_token}` or `Bearer {warehouse_manager_token}`

**Request Body (ScanData DTO):**
```json
{
  "shipment_id": 6,
  "from_warehouse_id": 1,
  "to_warehouse_id": 2,
  "transferred_by": 3,
  "reason": "Re-routing to closer hub for delivery"
}
```

**Validation Rules:**
- `shipment_id` — required, exists:shipments,id
- `from_warehouse_id` — required, exists:warehouses,id
- `to_warehouse_id` — required, exists:warehouses,id, different from from_warehouse_id
- `transferred_by` — required, exists:users,id
- `reason` — required, string

**Success Response (200):**
```json
{
  "message": "Shipment transferred successfully",
  "data": {
    "id": 6,
    "tracking_number": "CP-CAI-006",
    "state": "packed",
    "warehouse_id": 2,
    "transferred_at": "2024-02-15T10:00:00.000000Z"
  }
}
```

---

## 7. Routes & Fleet Management

### `POST /drivers`
Add a driver to the fleet.

**Authentication:** `Bearer {tenant_admin_token}`

**Request Body:**
```json
{
  "name": "Youssef Ali",
  "email": "driver@cairo-express.test",
  "phone": "+201000000003",
  "license_number": "LIC-0001",
  "vehicle_type": "van",
  "vehicle_plate": "CP-001",
  "max_capacity_kg": 500,
  "active": true
}
```

**Validation Rules:**
- `name` — required, string
- `email` — required, email, unique:users
- `phone` — required, string
- `license_number` — required, unique per tenant
- `vehicle_type` — required, `in:van,truck,motorcycle,bicycle`
- `vehicle_plate` — required, string
- `max_capacity_kg` — nullable, numeric
- `active` — boolean

**Success Response (201):**
```json
{
  "message": "Driver created successfully",
  "data": {
    "id": 1,
    "tenant_id": 1,
    "user_id": 4,
    "name": "Youssef Ali",
    "license_number": "LIC-0001",
    "vehicle_plate": "CP-001",
    "status": "available",
    "created_at": "2024-01-15T08:00:00.000000Z"
  }
}
```

**Error Responses:**
- `402/403/422` — SaaS subscription driver limit exceeded

**Side Effects:**
- Creates linked `User` record with role `Driver`
- Assigns Sanctum abilities `["shipments:update", "gps:update"]`

---

### `GET /drivers`
List all drivers for the tenant.

**Authentication:** `Bearer {tenant_admin_token}`

**Success Response (200):**
```json
{
  "message": "Drivers retrieved successfully",
  "data": [
    { "id": 1, "name": "Youssef Ali", "vehicle_plate": "CP-001", "status": "available", "current_route_id": null }
  ]
}
```

---

### `POST /routes`
Create a daily route manifest assigned to exactly one driver.

**Authentication:** `Bearer {tenant_admin_token}`

**Request Body (RouteAssignmentData DTO):**
```json
{
  "driver_id": 1,
  "date": "2024-02-15",
  "name": "Morning Manifest",
  "vehicle_id": 1,
  "estimated_start_time": "08:00:00",
  "estimated_end_time": "14:00:00",
  "shipment_ids": [2, 3, 4],
  "optimization_strategy": "shortest_path"
}
```

**Validation Rules:**
- `driver_id` — required, exists:drivers,id (tenant-scoped)
- `date` — required, date
- `name` — required, string
- `vehicle_id` — nullable, exists:vehicles,id
- `estimated_start_time` / `estimated_end_time` — nullable, time
- `shipment_ids` — nullable, array of exists:shipments,id
- `optimization_strategy` — nullable, `in:shortest_path,nearest_neighbor,tsp`

**Success Response (201):**
```json
{
  "message": "Route manifest created successfully",
  "data": {
    "id": 1,
    "tenant_id": 1,
    "driver_id": 1,
    "name": "Morning Manifest",
    "date": "2024-02-15",
    "status": "planned",
    "stops": [
      { "shipment_id": 2, "sequence": 1, "estimated_arrival": "08:30:00" },
      { "shipment_id": 3, "sequence": 2, "estimated_arrival": "09:15:00" }
    ],
    "created_at": "2024-02-15T07:00:00.000000Z"
  }
}
```

**Error Responses:**
- `422` — Driver already has a route for this date (exclusivity constraint)
- `422` — One or more shipment_ids invalid or already assigned to another route

**Side Effects:**
- Auto-generates route stops from shipment_ids
- Applies optimization algorithm if requested
- Updates shipment states to `assigned` if previously `packed`

---

### `POST /routes/{route}/start`
Mark a route as started.

**Authentication:** `Bearer {tenant_admin_token}` or `Bearer {driver_mobile_token}`

**Path Parameters:**
- `route` — Route ID (implicitly bound to `Route` model)

**Request Body:**
```json
{
  "started_at": "2024-02-15T08:05:00Z",
  "odometer_start": 15000
}
```

**Success Response (200):**
```json
{
  "message": "Route started successfully",
  "data": {
    "id": 1,
    "status": "active",
    "started_at": "2024-02-15T08:05:00.000000Z",
    "odometer_start": 15000
  }
}
```

**Side Effects:**
- Transitions route status `planned → active`
- Fires `RouteStartedEvent`

---

### `PATCH /routes/{route}/stops`
Reorder route stops manually or via re-optimization.

**Authentication:** `Bearer {tenant_admin_token}`

**Path Parameters:**
- `route` — Route ID

**Request Body (ReorderRouteStopsData DTO):**
```json
{
  "stops": [
    { "shipment_id": 3, "sequence": 1, "estimated_arrival": "08:30:00" },
    { "shipment_id": 2, "sequence": 2, "estimated_arrival": "09:00:00" }
  ],
  "optimization_applied": false,
  "reason": "Customer requested earlier delivery"
}
```

**Success Response (200):**
```json
{
  "message": "Route stops reordered successfully",
  "data": {
    "id": 1,
    "stops": [
      { "shipment_id": 3, "sequence": 1, "estimated_arrival": "08:30:00" },
      { "shipment_id": 2, "sequence": 2, "estimated_arrival": "09:00:00" }
    ],
    "updated_at": "2024-02-15T08:15:00.000000Z"
  }
}
```

---

### `GET /routes/{route}`
Get route details with driver, stops, and shipment relations.

**Authentication:** `Bearer {tenant_admin_token}`

**Path Parameters:**
- `route` — Route ID

**Success Response (200):**
```json
{
  "message": "Route retrieved successfully",
  "data": {
    "id": 1,
    "name": "Morning Manifest",
    "status": "active",
    "driver": { "id": 1, "name": "Youssef Ali", "vehicle_plate": "CP-001" },
    "stops": [ /* ... */ ],
    "shipments": [ /* ... */ ],
    "completed_stops": 2,
    "total_stops": 5
  }
}
```

---

## 8. Driver Mobile Operations

### `GET /mobile/manifest`
Retrieve the driver's assigned route and stops for today.

**Authentication:** `Bearer {driver_mobile_token}`

**Success Response (200):**
```json
{
  "message": "Manifest retrieved successfully",
  "data": {
    "route": {
      "id": 1,
      "name": "Morning Manifest",
      "status": "active",
      "started_at": "2024-02-15T08:05:00Z"
    },
    "stops": [
      {
        "sequence": 1,
        "shipment": {
          "id": 3,
          "tracking_number": "CP-CAI-003",
          "recipient_name": "Customer A",
          "destination_address": "15 Ramses St",
          "destination_lat": 30.0458,
          "destination_lng": 31.2361,
          "cod_amount": 125,
          "state": "assigned"
        },
        "status": "pending",
        "estimated_arrival": "08:30:00"
      }
    ],
    "stats": { "total_stops": 5, "completed": 0, "remaining": 5 }
  }
}
```

**Error Responses:**
- `404` — No route assigned to driver for today

---

### `POST /mobile/shipments/{id}/deliver`
Mark a shipment as delivered with signature and COD collection.

**Authentication:** `Bearer {driver_mobile_token}`

**Path Parameters:**
- `id` — Shipment ID

**Request Body:**
```json
{
  "delivered_at": "2024-02-15T10:30:00Z",
  "signature": "data:image/png;base64,iVBORw0KGgo...",
  "recipient_name": "John Doe",
  "photos": [
    "data:image/jpeg;base64,/9j/4AAQSkZJRg..."
  ],
  "cod_collected": 50.00,
  "notes": "Handed to receptionist"
}
```

**Validation Rules:**
- `delivered_at` — required, date
- `signature` — required, string (base64 image data URI)
- `recipient_name` — required, string
- `photos` — nullable, array of base64 strings (max 3)
- `cod_collected` — required if shipment `cod_amount > 0`, numeric, must equal `shipment.cod_amount`
- `notes` — nullable, string

**Success Response (200):**
```json
{
  "message": "Shipment delivered successfully",
  "data": {
    "id": 3,
    "tracking_number": "CP-CAI-003",
    "state": "delivered",
    "delivered_at": "2024-02-15T10:30:00.000000Z",
    "signature_url": "https://cdn.cargopulse.test/signatures/sig_xxx.png",
    "cod_collected": 50.00,
    "photos": [
      "https://cdn.cargopulse.test/photos/photo_xxx.jpg"
    ]
  }
}
```

**Side Effects:**
- Transitions state to `DeliveredState`
- Creates `StatusLog` entry
- Fires `ShipmentDeliveredEvent` → queues:
  - `SendDeliveryWebhookToMerchant`
  - `UpdateDriverMetrics`
  - `PushDeliveryNotificationToCustomer`
- Creates positive `LedgerTransaction` for COD
- Creates negative `LedgerTransaction` for delivery fee
- If all route stops completed, auto-updates route status to `completed`

---

### `POST /mobile/shipments/{id}/fail`
Mark a delivery attempt as failed.

**Authentication:** `Bearer {driver_mobile_token}`

**Path Parameters:**
- `id` — Shipment ID

**Request Body:**
```json
{
  "failed_at": "2024-02-15T11:00:00Z",
  "reason": "recipient_not_available",
  "notes": "Called twice, no answer at door",
  "next_attempt_date": "2024-02-16",
  "photos": []
}
```

**Validation Rules:**
- `failed_at` — required, date
- `reason` — required, `in:recipient_not_available,address_incorrect,refused, damaged,other`
- `notes` — nullable, string
- `next_attempt_date` — nullable, date, after `failed_at`
- `photos` — nullable, array

**Success Response (200):**
```json
{
  "message": "Shipment marked as failed",
  "data": {
    "id": 6,
    "tracking_number": "CP-CAI-006",
    "state": "failed",
    "failed_at": "2024-02-15T11:00:00.000000Z",
    "failure_reason": "recipient_not_available",
    "next_attempt_date": "2024-02-16"
  }
}
```

**Side Effects:**
- Transitions to `FailedState`
- Fires `ShipmentFailedEvent`
- Queues retry scheduling job if `next_attempt_date` provided

---

### `POST /drivers/location`
Ingest a GPS coordinate from the driver mobile app. **Writes directly to Redis only — zero PostgreSQL I/O.**

**Authentication:** `Bearer {driver_mobile_token}`

**Request Body (DriverLocationData DTO):**
```json
{
  "lat": 30.0444,
  "lng": 31.2357,
  "timestamp": "2024-02-15T10:30:05Z",
  "accuracy": 4.5,
  "speed": 35.2,
  "heading": 270
}
```

**Validation Rules:**
- `lat` — required, numeric, between -90 and 90
- `lng` — required, numeric, between -180 and 180
- `timestamp` — required, date
- `accuracy` — nullable, numeric, min 0
- `speed` — nullable, numeric, min 0
- `heading` — nullable, numeric, between 0 and 360

**Success Response (202 Accepted):**
```json
{
  "message": "Location ingested successfully"
}
```

**Performance Requirement:** Response time **< 200ms**.

**Business Logic:**
1. `Redis::geoadd("tenant:{tenantId}:drivers:geo", lng, lat, driverId)` — updates geospatial index
2. `Redis::hset("tenant:{tenantId}:drivers:data", driverId, json_encode([...]))` — stores metadata hash
3. Broadcasts update via Laravel Reverb WebSocket to tracking channel
4. PostgreSQL `driver_locations` table remains untouched

**Batch Mode:**
```json
{
  "batch": [
    {"lat": 30.0444, "lng": 31.2357, "timestamp": "2024-02-15T10:30:05Z"},
    {"lat": 30.0445, "lng": 31.2358, "timestamp": "2024-02-15T10:30:10Z"}
  ]
}
```

---

## 9. Public Tracking

### `GET /track/{tracking_number}`
Public tracking endpoint. **No authentication required.**

**Authentication:** None

**Path Parameters:**
- `tracking_number` — Shipment tracking number (e.g., `CP-CAI-005`)

**Success Response (200):**
```json
{
  "message": "Tracking information retrieved",
  "data": {
    "tracking_number": "CP-CAI-005",
    "state": "delivered",
    "merchant_name": "Nile Store",
    "service_type": "standard",
    "created_at": "2024-01-10T08:00:00Z",
    "estimated_delivery": "2024-01-10T18:00:00Z",
    "delivered_at": "2024-01-10T14:30:00Z",
    "recipient_name": "John Doe",
    "current_location": {
      "lat": 30.0444,
      "lng": 31.2357,
      "updated_at": "2024-01-10T14:25:00Z"
    },
    "status_history": [
      { "state": "pending", "at": "2024-01-10T08:00:00Z" },
      { "state": "confirmed", "at": "2024-01-10T09:00:00Z" },
      { "state": "packed", "at": "2024-01-10T10:00:00Z" },
      { "state": "in_transit", "at": "2024-01-10T12:00:00Z" },
      { "state": "delivered", "at": "2024-01-10T14:30:00Z" }
    ]
  }
}
```

**Business Logic:**
- Reads latest coordinates from Redis Geospatial index (fast, no DB hit for location)
- Reads shipment metadata from PostgreSQL (cached)
- If Redis has no recent data, falls back to last known PostgreSQL location

**Error Responses:**
- `404` — Tracking number not found

---

## 10. Analytics & Dashboard

### `GET /analytics/metrics`
Retrieve global KPI metrics strictly scoped to the active tenant.

**Authentication:** `Bearer {tenant_admin_token}`

**Success Response (200):**
```json
{
  "message": "Operational metrics retrieved successfully",
  "data": {
    "tenant_id": 1,
    "total_shipments": 1250,
    "pending_shipments": 45,
    "in_transit_shipments": 120,
    "delivered_today": 89,
    "failed_today": 3,
    "active_routes": 12,
    "available_drivers": 8,
    "busy_drivers": 12,
    "fleet_utilization": 0.60,
    "cod_collected_today": 15200.00,
    "cod_pending": 45000.00,
    "avg_delivery_time_minutes": 245,
    "on_time_rate": 0.94
  }
}
```

**Business Logic:**
- Executes `GetTenantDashboardMetricsAction`
- Aggregates from PostgreSQL with Redis caching layer (TTL: 5 minutes)
- All counts filtered by `tenant_id` via global scope

---

### `GET /shipments/search`
*(Documented in Section 5 — included here for analytics context)*

Advanced filtering with pagination. Supports complex combinations of state, merchant, tracking number, and date ranges.

---

## 11. Event-Driven Workflows

> The following endpoints are typically exposed via internal/test routes for debugging and integration verification.

### `POST /test/events/shipment-delivered`
Manually fire `ShipmentDeliveredEvent` to verify listener registration and Horizon queue behavior.

**Authentication:** `Bearer {tenant_admin_token}` (or Super Admin)

**Request Body:**
```json
{
  "shipment_id": 5
}
```

**Success Response (200):**
```json
{
  "message": "ShipmentDeliveredEvent dispatched",
  "data": {
    "event": "ShipmentDeliveredEvent",
    "shipment_id": 5,
    "queued_jobs": [
      "SendDeliveryWebhookToMerchant",
      "UpdateDriverMetrics",
      "PushDeliveryNotificationToCustomer"
    ],
    "horizon_queue": "default",
    "dispatched_at": "2024-02-15T10:35:00.000000Z"
  }
}
```

---

### `POST /test/jobs/persist-gps`
Manually trigger `PersistRedisGpsDataJob` to force batch-insert Redis coordinates into PostgreSQL PostGIS.

**Authentication:** `Bearer {super_admin_token}` or `Bearer {tenant_admin_token}`

**Success Response (200):**
```json
{
  "message": "PersistRedisGpsDataJob dispatched",
  "data": {
    "job_id": "uuid",
    "records_to_persist": 1240,
    "target_table": "location_histories",
    "estimated_runtime": "3s"
  }
}
```

**Business Logic:**
- Scans Redis keys matching `tenant:*:drivers:data`
- Bulk-inserts into `location_histories` (PostGIS-enabled PostgreSQL)
- Clears processed Redis keys
- Runs every 5 minutes via Laravel Scheduler normally

---

### `POST /test/webhook/retry`
Force a webhook retry with simulated failure to test exponential backoff configuration.

**Authentication:** `Bearer {tenant_admin_token}`

**Request Body:**
```json
{
  "merchant_id": 1,
  "event": "shipment.delivered",
  "force_failure": true,
  "failure_status": 500
}
```

**Success Response (202):**
```json
{
  "message": "Webhook retry test queued",
  "data": {
    "attempts": 1,
    "max_attempts": 3,
    "backoff_seconds": 60,
    "next_retry_at": "2024-02-15T10:36:00.000000Z"
  }
}
```

**Backoff Strategy:**
- Attempt 1: immediate
- Attempt 2: 1 minute
- Attempt 3: 5 minutes
- Attempt 4: 15 minutes
- After max attempts: moved to `failed_jobs` table

---

## 12. Error Reference

### Standard Error Format
```json
{
  "message": "Error description",
  "errors": {
    "field_name": ["Error detail 1", "Error detail 2"]
  }
}
```

### HTTP Status Codes

| Code | Meaning | Common Causes |
|------|---------|---------------|
| `200` | OK | Standard success |
| `201` | Created | Resource created (shipments, drivers, routes) |
| `202` | Accepted | Async job queued (GPS ingestion, invoice generation) |
| `207` | Multi-Status | Bulk import partial success |
| `400` | Bad Request | Malformed JSON, invalid parameters |
| `401` | Unauthorized | Missing or invalid Sanctum token |
| `402` | Payment Required | SaaS subscription limit exceeded, Stripe declined |
| `403` | Forbidden | RBAC permission denied (spatie/laravel-permission) |
| `404` | Not Found | Resource missing OR cross-tenant access blocked by TenantScope |
| `409` | Conflict | Illegal state transition, duplicate route assignment |
| `422` | Unprocessable Entity | Validation failure (Form Request) |
| `429` | Too Many Requests | Redis-based rate limit exceeded |
| `500` | Internal Server Error | Unhandled exception |

### Specific Error Scenarios

**Tenant Isolation (404, not 403)**
> When Tenant A attempts to access Tenant B's resource, the API returns `404 Not Found` rather than `403 Forbidden`. This prevents resource enumeration attacks and is enforced by `TenantScope` automatically appending `WHERE tenant_id = ?` to every query.

**State Machine (422/409)**
> Illegal transitions (e.g., `pending` → `delivered`) throw `TransitionNotAllowed` from `spatie/laravel-model-states`, resulting in `422` or `409` with message: *"Transition from PendingState to DeliveredState is not allowed."*

**SaaS Subscription Gate (402/422)**
> Creating a driver beyond the tier limit returns `402 Payment Required` or `422` with message: *"Driver limit reached (50/50). Upgrade your subscription to add more drivers."*

**Webhook Retry**
> Failed webhooks do not return errors to the driver/mobile client. Failures are handled asynchronously by Horizon with exponential backoff.

---

## Appendix: Postman Variable Mapping

| Variable | Populated By | Used In |
|----------|--------------|---------|
| `{{base_url}}` | Environment | All requests |
| `{{tenant_admin_token}}` | `00. Login as Cairo Admin` | Tenant-scoped admin requests |
| `{{super_admin_token}}` | `00. Login as Super Admin` | Platform endpoints |
| `{{warehouse_manager_token}}` | `00. Login as Cairo Warehouse Manager` | RBAC tests |
| `{{driver_mobile_token}}` | `00. Login as Cairo Driver` | Mobile namespace |
| `{{merchant_api_key}}` | `00. Login as Nile Store Merchant` | B2B API calls |
| `{{current_tenant_id}}` | Login response | Tenant isolation tests |
| `{{current_shipment_id}}` | `POST /shipments` | State transitions, tracking |
| `{{current_tracking_number}}` | `POST /shipments` | Public tracking |
| `{{current_route_id}}` | `POST /routes` | Route operations |
| `{{current_driver_id}}` | Login / `POST /drivers` | Route assignment |
| `{{current_merchant_id}}` | Merchant login | Ledger, invoices |
| `{{current_warehouse_id}}` | `POST /warehouses` | Check-in, transfer |
| `{{current_invoice_id}}` | Invoice generation | Invoice retrieval |

---

*Generated for CargoPulse API v1 — Multi-tenant SaaS Logistics Platform*
