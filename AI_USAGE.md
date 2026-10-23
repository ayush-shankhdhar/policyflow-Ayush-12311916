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
- **Automated Test Execution:** Tests were run locally using PHPUnit with SQLite. Actual test results should be reported based on the recorded test output.
