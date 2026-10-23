# PolicyFlow Engine

A fault‑tolerant insurance issuance and reconciliation engine built with **Laravel 11**.

## Project Overview

PolicyFlow receives policy requests, calculates premiums, submits them to a (mock) insurer, and handles asynchronous callbacks for issuance, failures, time‑outs and reconciliation. The system is designed to be **idempotent**, **retry‑aware**, and **state‑machine driven** to guarantee reliable policy issuance.

## Architecture

- **Models**: `PolicyRequest`, `IssuanceAttempt`, `ProviderEvent`
- **Services**:
  - `PremiumCalculator` – calculates premium based on age, destination, plan, sum insured.
  - `PolicyStateMachine` – enforces allowed state transitions.
  - `MockInsurerService` – deterministic mock insurer with configurable scenarios.
- **Controllers**:
  - `PolicyRequestController` – CRUD, submit, and state handling.
  - `MockInsurerCallbackController` – processes provider callbacks idempotently.
- **Configuration**: `config/mock_insurer.php` – set `MOCK_INSURER_SCENARIO` (e.g. `SUCCESS`, `TEMPORARY_FAILURE`).
- **Database**: SQLite for local dev, compatible with MySQL 8+ / PostgreSQL.

## Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test   # run the test suite
```

To use MySQL or PostgreSQL, update `.env` accordingly and run the migrations again.

## API Endpoints

| Method | URI | Description |
|--------|-----|-------------|
| `POST` | `/api/policy-requests` | Create a new policy request and calculate premium. |
| `GET`  | `/api/policy-requests/{id}` | Retrieve policy details, attempts and events. |
| `POST` | `/api/policy-requests/{id}/submit` | Submit the request to the insurer (idempotent). |
| `GET`  | `/api/policy-requests` | List policies with optional `?state=` filter. |
| `POST` | `/mock-insurer/callback` | Provider callback webhook (idempotent). |

## Sample cURL Requests

```bash
# Create request
curl -X POST http://localhost/api/policy-requests \
  -H "Content-Type: application/json" \
  -d '{
    "client_request_id": "REQ123",
    "agent_id": "AGENT1",
    "customer_name": "John Doe",
    "date_of_birth": "1990-01-01",
    "destination": "DOMESTIC",
    "trip_start": "2024-12-01",
    "trip_end": "2024-12-05",
    "plan_code": "PLUS",
    "sum_insured": 100000
}'

# Submit for issuance
curl -X POST http://localhost/api/policy-requests/1/submit

# Provider callback (example)
curl -X POST http://localhost/mock-insurer/callback \
  -H "Content-Type: application/json" \
  -d '{
    "event_id": "ev123",
    "policy_id": 1,
    "type": "SUCCESS",
    "provider_reference": "PRV000001",
    "policy_number": "POLABCDEF"
}'
```

## Testing

The test suite covers:
- Core happy‑path creation and premium calculation.
- Duplicate request handling.
- Validation of trip dates.
- **Workflow scenarios** (temporary failure retry, timeout with reconciliation, duplicate callbacks, invalid state handling).

Run tests:
```
php artisan test
```

All tests should pass locally with SQLite. For MySQL/PostgreSQL, ensure the DB is reachable and run the same command.

## Reliability Scenarios

- **Temporary failures** – the client can retry `POST /policy-requests/{id}/submit` until a success state is reached.
- **Timeout after issue** – request moves to `NEEDS_RECONCILIATION` and will be resolved via a callback.
- **Duplicate callbacks** – identified by `event_id` and ignored after the first processing.
- **Idempotent submissions** – repeated submit calls return existing issuance info.

## Assumptions & Limitations

- The mock insurer is deterministic; in production a real provider integration would replace `MockInsurerService`.
- Concurrency handling for duplicate submissions is limited to DB unique constraints.
- No external queue system is used; all state changes happen synchronously within the request lifecycle.

## .env.example

```ini
APP_NAME=PolicyFlow
APP_ENV=local
APP_KEY=base64:GENERATE_WITH_ARTISAN
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
# For MySQL or PostgreSQL, configure the usual DB_* variables.

CACHE_DRIVER=file
QUEUE_CONNECTION=sync

MOCK_INSURER_SCENARIO=SUCCESS
```

## AI_USAGE.md

*ChatGPT and Google Gemini were used in a limited capacity during development to clarify technical concepts, discuss implementation approaches, and assist with understanding potential issues.
The developer independently worked on the application logic, implementation decisions, debugging, and verification of the project. The implementation was checked using available automated tests and manual verification, as applicable.*
