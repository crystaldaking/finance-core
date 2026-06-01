# Architecture

Crystal Finance Core is a pure PHP domain package. The package exposes financial primitives and contracts, not persistence models or framework services.

## Package Boundaries

- `crystaldaking/finance-core`: money, network-aware assets, ledger, generic fees, audit, events, idempotency.
- `crystaldaking/payments-core`: future package for payment lifecycles, providers, webhooks, refunds, chargebacks, payouts, and settlement.
- Persistence adapters, Laravel integration, reports, taxes, fiscal periods, and UI are outside v1.

## Runtime Rules

- Financial values are represented by decimal strings and `brick/math` types.
- Assets are identified by `AssetId`; crypto assets include an explicit network.
- Ledger transactions are append-only domain facts and must balance per asset.
- Idempotency is expressed through value objects and storage interfaces; concrete stores live outside this package.
- Domain events are recorded and released, never dispatched by the core.

## Dependency Rules

`src/` may depend on PHP, `brick/math`, and PSR-20 clock interfaces. It must not import Laravel, Symfony FrameworkBundle, HttpFoundation, Doctrine ORM, Carbon, database clients, queues, or service containers.
