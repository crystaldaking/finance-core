# Changelog

All notable changes to `crystaldaking/finance-core` are documented here.

The project follows Semantic Versioning after `v1.0.0`.

## [1.0.0] - 2026-06-01

### Added

- Framework-agnostic PHP 8.5 finance core under `Crystal\Finance\Core`.
- Network-aware `Asset` and `AssetId` model for fiat and crypto assets such as `USDT@TRON` and `USDT@ETHEREUM`.
- Decimal-safe `Money`, `Percentage`, `Rate`, `ExchangeRate`, allocation, rounding, and minor-unit string APIs.
- Double-entry ledger primitives with transaction validation, per-asset balancing, opaque account ids, metadata, references, and explicit reversals.
- Generic fee engine with percentage, fixed, minimum, maximum, net, gross, total fee, and explainable breakdown lines.
- Audit context, domain event, event recording, idempotency key, payload fingerprint, idempotency record, and idempotency runner primitives.
- Ten executable examples plus an executable merchant finance demo.
- CI, Dependabot, issue templates, pull request template, coverage gate, mutation testing configuration, and release checklist.

### Security

- Financial modules reject floating point arithmetic.
- Idempotency fingerprints reject floating point payload values and canonicalize object key order.
- Architecture tests enforce framework independence and strict source file boundaries.

## [0.9.0] - 2026-06-01

### Added

- Public API freeze documentation and final examples before `v1.0.0`.

## [0.5.0] - 2026-06-01

### Added

- Audit, domain events, and idempotency primitives.

## [0.4.0] - 2026-06-01

### Added

- Generic fee and charge calculation engine.

## [0.3.0] - 2026-06-01

### Added

- Double-entry ledger primitives and validation.

## [0.2.0] - 2026-06-01

### Added

- Money and network-aware asset model.

## [0.1.0] - 2026-06-01

### Added

- Repository scaffold, Composer metadata, tooling, docs skeleton, and Apache-2.0 license.
