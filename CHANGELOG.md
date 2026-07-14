# Changelog

All notable changes to `crystaldaking/finance-core` are documented here.

The project follows Semantic Versioning after `v1.0.0`.

## [Unreleased]

## [1.1.0] - 2026-07-15

### Added

- Add `LedgerReversalRepository` and `Ledger::appendReversal()` as the atomic persistence boundary for an original transaction and its reversal.
- Add immutable idempotency claim ids so store adapters can fence stale processing attempts.

### Changed

- Start completed idempotency replay retention when the callback finishes rather than when processing begins.
- Update the idempotency example to reclaim expired records in accordance with the store contract.

### Fixed

- Require ledger reversals to be created from an original transaction, validate both sides of the pair, and reject direct construction of reversal transactions.
- Reject non-positive idempotency TTLs.
- Preserve custom minimum and maximum fee labels in adjustment breakdown lines.
- Reject negative gross amounts in fee calculations.

### Security

- Prevent stale idempotency workers from completing or failing a newer claim for the same key.

## [1.0.1] - 2026-06-01

### Fixed

- Reject `Money` operations when two values share an `AssetId` but have incompatible asset configuration such as different scale.
- Keep crypto and fiat asset network invariants symmetric, including rejection of fiat assets with a network-aware id.
- Report malformed asset ids such as `CODE@NETWORK@EXTRA` as malformed ids instead of registry misses.
- Use precise domain exceptions for invalid asset pairs, exchange conversion source mismatches, fee context metadata, and fee result invariants.
- Enforce `FeeResult` consistency at construction time: `net` must equal `gross - totalFee`, and breakdown lines must sum to `totalFee`.
- Validate fee, audit, ledger, and event metadata keys and values at runtime instead of relying only on PHPDoc.
- Prevent ledger transactions with reversal state from being modified with additional entries or metadata.

### Changed

- Added safe factory helpers for fee breakdowns and breakdown lines while preserving the existing constructors with runtime validation.
- Fee calculation now validates fixed, minimum, and maximum fee component assets against the gross amount using full asset compatibility.

## [1.0.0] - 2026-06-01

### Added

- Framework-agnostic PHP 8.5 finance core under `Crystal\Finance\Core`.
- Network-aware `Asset` and `AssetId` model for fiat and crypto assets such as `USDT@TRON` and `USDT@ETHEREUM`.
- Decimal-safe `Money`, `Percentage`, `Rate`, `ExchangeRate`, allocation, rounding, and minor-unit string APIs.
- Double-entry ledger primitives with transaction validation, per-asset balancing, opaque account ids, metadata, references, and explicit reversals.
- Generic fee engine with percentage, fixed, minimum, maximum, net, gross, total fee, and explainable breakdown lines.
- Audit context, domain event, event recording, idempotency key, payload fingerprint, idempotency record, and idempotency runner primitives.
- Ten executable real-world examples, including an end-to-end merchant finance flow.
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
