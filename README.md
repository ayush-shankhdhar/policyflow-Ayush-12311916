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
## Setup Instructions

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan test   # run the test suite
```

### Database Configuration & Compatibility

The application is fully compatible with **SQLite**, **MySQL 8+**, and **PostgreSQL**.
- **SQLite (Default for fast local testing)**:
  ```env
  DB_CONNECTION=sqlite
  DB_DATABASE=database/database.sqlite
  ```
- **MySQL 8+**:
  ```env
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=policyflow
  DB_USERNAME=root
  DB_PASSWORD=secret
  ```
- **PostgreSQL**:
  ```env
  DB_CONNECTION=pgsql
  DB_HOST=127.0.0.1
  DB_PORT=5432
  DB_DATABASE=policyflow
  DB_USERNAME=postgres
  DB_PASSWORD=secret
  ```

All migrations utilize standard Laravel Schema types (`id()`, `string()`, `date()`, `decimal(12, 2)`, `enum()`, `json()`, `foreignId()`, and composite unique indexes `['agent_id', 'client_request_id']`), ensuring identical behavior across MySQL, PostgreSQL, and SQLite.
*Note: Due to local machine service availability, the automated test suite was verified locally against SQLite; the migration schemas and Eloquent queries have zero engine-specific SQL dependencies.*

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
# AI Usage Disclosure — POLICYFLOW

## 1. Tools Used
- **Google Gemini:** Used for limited technical guidance and clarification during development.
- **ChatGPT:** Used for referencing Laravel 11 route conventions, PHPUnit assertions, and discussing implementation approaches.

## 2. Areas Where AI Assisted
- **Architecture & Scaffolding:** Used for discussing Laravel models, state machine structure, and database schema design for `policy_requests`, `issuance_attempts`, and `provider_events`.
- **Test Development:** Assisted with understanding automated feature tests for temporary failure retries, timeout reconciliation, idempotent callback handling, and invalid state transitions.
- **Troubleshooting:** Used for technical clarification when investigating test failures and Laravel-specific behavior.

## 3. Human Oversight & Engineering Verification
- **Pricing Logic Verification:** Checked the premium calculation against the assignment specification, including base rates, age brackets, coverage tiers, inclusive trip duration, and destination multipliers.
- **Idempotency & Concurrency:** Reviewed the composite unique constraint `(agent_id, client_request_id)` and provider event deduplication to prevent duplicate policy issuance.
- **State Machine Integrity:** Reviewed state transition rules to protect issued policies from invalid backward transitions.
- **Automated Test Execution:** Tests were run locally using PHPUnit with SQLite. Actual test results should be reported based on the recorded test output.*
