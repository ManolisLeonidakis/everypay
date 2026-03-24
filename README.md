# EveryPay

Payment processing platform built with Laravel 13, Inertia.js and React.

---

## Table of Contents

- [Requirements](#requirements)
- [Quick Start with Docker](#quick-start-with-docker)
- [Architecture](#architecture)
- [API Reference](#api-reference)
- [Adding a New Payment Provider](#adding-a-new-payment-provider)

---

## Requirements

The only thing you need installed on your machine is **Docker Desktop**.

- [Download Docker Desktop](https://www.docker.com/products/docker-desktop/) (available for macOS, Windows, Linux)

---

## Quick Start with Docker

### 1. Clone the repository

```bash
git clone <repo-url>
cd everypay
```

### 2. Create your environment file

```bash
cp .env.example .env
```

The default configuration uses **SQLite** and requires no additional services.

### 3. Start the containers

```bash
docker compose up --build
```

This will:
- Build the PHP-FPM container and install all PHP dependencies via Composer
- Start the Nginx web server
- Start the Node.js container, install npm packages and run Vite
- Create the SQLite database, run all migrations, and seed a demo merchant

### 4. Open the application

| Service | URL |
|---|---|
| Web application | http://localhost:8000 |
| Vite dev server (HMR) | http://localhost:5173 |

### 5. Login credentials (seeded demo merchant)

| Field | Value |
|---|---|
| Email | `merchant@example.com` |
| Password | `password` |

---

### Common Docker commands

```bash
# Start in the background
docker compose up -d --build

# Stop containers
docker compose down

# View logs
docker compose logs -f

# Run an artisan command
docker compose exec app php artisan <command>

# Access PHP shell inside the container
docker compose exec app sh

# Re-run migrations and seeders (resets all data)
docker compose exec app php artisan migrate:fresh --seed --force
```

---

## Architecture

```
everypay/
├── app/
│   ├── Contracts/                      # Interfaces
│   │   ├── PaymentProviderInterface.php
│   │   └── TransactionRepositoryInterface.php
│   ├── Enums/
│   │   └── TransactionStatus.php       # succeeded | failed
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php              # Web login / logout
│   │   │   ├── DashboardController.php         # Web transactions view
│   │   │   └── Api/V1/
│   │   │       ├── TokenController.php         # POST /api/v1/tokens
│   │   │       ├── ChargeController.php        # POST /api/v1/charges
│   │   │       └── TransactionController.php   # GET  /api/v1/transactions
│   │   ├── Requests/
│   │   │   ├── ChargeRequest.php
│   │   │   └── TransactionIndexRequest.php
│   │   └── Resources/
│   │       └── TransactionResource.php
│   ├── Models/
│   │   ├── Merchant.php                # Authenticatable, has API tokens
│   │   └── Transaction.php
│   ├── PaymentMethods/
│   │   └── FakeStripe.php      # Built-in test provider
│   ├── Providers/
│   │   └── AppServiceProvider.php      # IoC bindings
│   ├── Repositories/
│   │   └── EloquentTransactionRepository.php
│   └── Services/Psp/
│       ├── ChargeData.php              # Input value object
│       ├── ChargeResult.php            # Output value object
│       └── PspFactory.php             # Resolves driver → provider
├── docker/
│   ├── php/
│   │   ├── Dockerfile                  # PHP 8.3-FPM image
│   │   └── entrypoint.sh              # Startup script (migrate, seed, etc.)
│   └── nginx/
│       └── default.conf               # Nginx → PHP-FPM proxy
├── docker-compose.yml                  # app (php-fpm) + web (nginx) + node (vite)
└── routes/
    ├── api.php                         # All API routes
    └── web.php                         # Login / dashboard
```

### Key design decisions

| Pattern | Where | Why |
|---|---|---|
| Repository pattern | `TransactionRepositoryInterface` | Decouples persistence from business logic |
| Strategy pattern | `PaymentProviderInterface` | Swap payment providers without touching controller code |
| Factory | `PspFactory` | Resolves a string driver name to a concrete provider |
| Value objects | `ChargeData`, `ChargeResult` | Typed, immutable data transfer between layers |

### Request lifecycle (charge)

```
POST /api/v1/charges
  → Sanctum auth middleware
  → ChargeRequest (validation)
  → ChargeController
      → PspFactory::make($merchant->psp_driver)      # pick provider
      → PaymentProviderInterface::charge(ChargeData)  # call provider
      → TransactionRepository::create(...)            # persist result
  → TransactionResource (JSON response)
```

---

## API Reference

All API endpoints are prefixed with `/api/v1`.
Authentication uses **Laravel Sanctum** Bearer tokens.

---

### `POST /api/v1/tokens` — Get an API token

No authentication required.

**Request body**

```json
{
  "email": "merchant@example.com",
  "password": "password",
  "device_name": "my-integration"
}
```

| Field | Type | Required | Description |
|---|---|---|---|
| `email` | string | yes | Merchant email |
| `password` | string | yes | Merchant password |
| `device_name` | string | no | Label for the token (default: `api-token`) |

**Response `201`**

```json
{
  "token": "1|abc123..."
}
```

---

### `POST /api/v1/charges` — Create a charge

**Headers**

```
Authorization: Bearer <token>
Content-Type: application/json
```

**Request body**

```json
{
  "amount": 1500,
  "currency": "EUR",
  "description": "Order #42",
  "card_number": "4242424242424242",
  "cvv": "123",
  "expiry_month": 12,
  "expiry_year": 2027
}
```

| Field | Type | Required | Notes |
|---|---|---|---|
| `amount` | integer | yes | Amount in **cents** (e.g. `1500` = €15.00) |
| `currency` | string | no | ISO 4217, default `EUR` |
| `description` | string | no | Max 255 chars |
| `card_number` | string | yes | Exactly 16 digits |
| `cvv` | string | yes | 3–4 digits |
| `expiry_month` | integer | yes | 1–12 |
| `expiry_year` | integer | yes | Current year or later |

**Response `201`**

```json
{
  "data": {
    "id": 1,
    "amount": 1500,
    "currency": "EUR",
    "description": "Order #42",
    "card_last_four": "4242",
    "status": "succeeded",
    "psp_reference": "fakestripe_XXXXXXXXXXXXXXXX",
    "created_at": "2026-03-24T13:00:00+00:00"
  }
}
```

> **FakeStripe simulation rule:** cards ending in an **even** digit succeed; cards ending in an **odd** digit fail.

---

### `GET /api/v1/transactions` — List transactions

**Headers**

```
Authorization: Bearer <token>
```

**Query parameters**

| Parameter | Type | Description |
|---|---|---|
| `from` | date (Y-m-d) | Filter from this date (inclusive) |
| `to` | date (Y-m-d) | Filter to this date (inclusive) |
| `per_page` | integer (1–100) | Transactions per page, default 15 |

**Example**

```
GET /api/v1/transactions?from=2026-03-01&to=2026-03-31&per_page=25
```

**Response `200`**

```json
{
  "data": [
    {
      "id": 5,
      "amount": 500,
      "currency": "EUR",
      "description": null,
      "card_last_four": "1235",
      "status": "failed",
      "psp_reference": "fakestripe_YYYYYYYYYYYYYYYY",
      "created_at": "2026-03-24T10:30:00+00:00"
    }
  ],
  "links": { "...": "..." },
  "meta": { "current_page": 1, "last_page": 2, "total": 20 }
}
```

---

### Full example with cURL

```bash
# 1. Get token
TOKEN=$(curl -s -X POST http://localhost:8000/api/v1/tokens \
  -H "Content-Type: application/json" \
  -d '{"email":"merchant@example.com","password":"password"}' \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

# 2. Create a charge (card ending in even digit → succeeds)
curl -X POST http://localhost:8000/api/v1/charges \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1500,
    "currency": "EUR",
    "card_number": "4242424242424242",
    "cvv": "123",
    "expiry_month": 12,
    "expiry_year": 2027
  }'

# 3. List transactions
curl http://localhost:8000/api/v1/transactions \
  -H "Authorization: Bearer $TOKEN"
```

---

## Adding a New Payment Provider

The system uses a **Strategy + Factory** pattern. Adding a new provider takes 1 step.

### Create the provider class

Create a new file in `app/PaymentMethods/`, implementing `PaymentProviderInterface`:

```php
// app/PaymentMethods/StripeProvider.php

namespace App\PaymentMethods;

use App\Contracts\PaymentProviderInterface;
use App\Services\Psp\ChargeData;
use App\Services\Psp\ChargeResult;

class StripeProvider implements PaymentProviderInterface
{
    public function __construct(private readonly string $secretKey)
    {
    }

    public function charge(ChargeData $data): ChargeResult
    {
        // Call the real Stripe API here…
        // $response = \Stripe\Charge::create([...]);

        // On success:
        return ChargeResult::success(
            reference: $response->id,
            message: 'Charge succeeded.',
            raw: $response->toArray(),
        );

        // On failure:
        // return ChargeResult::failure(
        //     reference: $response->id,
        //     message: $response->failure_message,
        //     raw: $response->toArray(),
        // );
    }
}
```
