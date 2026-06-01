# Architecture

Crystal Finance Core is a pure PHP domain package.

The core models financial concepts and invariants only. It intentionally avoids persistence, framework integrations, transport adapters, payment providers, and UI concerns.

## Package Boundaries

- `finance-core`: money, network-aware assets, ledger, fees, audit, events, idempotency.
- `payments-core`: future package for payment lifecycles, providers, webhooks, refunds, chargebacks, payouts, and settlement.

## Runtime Rules

- Financial values are represented by decimal strings and `brick/math` types.
- Ledger transactions are append-only domain facts and must balance per asset.
- Idempotency is expressed through value objects and storage interfaces; concrete stores live outside this package.
