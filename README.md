# Crystal Finance Core

`crystaldaking/finance-core` is a framework-agnostic PHP financial core for applications that need safe money arithmetic, network-aware assets, double-entry ledgering, explainable fees, audit context, domain events, and idempotency primitives.

It is not a PSP application, a Laravel package, a database schema, or a UI. Payment workflows and provider integrations belong in future packages such as `crystaldaking/payments-core`.

## Installation

```bash
composer require crystaldaking/finance-core
```

## Quality

```bash
composer qa
```

## Principles

- No floating point arithmetic for financial values.
- Ledger balances are derived from entries, not stored directly by this package.
- Ledger transactions must balance per asset.
- The core has no Laravel, Symfony, Doctrine ORM, Carbon, database, queue, or framework dependency.
- Public APIs use explicit value objects instead of framework models or magic arrays.
