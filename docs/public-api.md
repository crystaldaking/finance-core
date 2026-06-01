# Public API

The package exposes stable primitives for generic financial applications. It intentionally does not expose payment lifecycle concepts.

## Stable Namespaces

- `Crystal\Finance\Core\Money`
- `Crystal\Finance\Core\Ledger`
- `Crystal\Finance\Core\Fee`
- `Crystal\Finance\Core\Audit`
- `Crystal\Finance\Core\Event`
- `Crystal\Finance\Core\Idempotency`
- `Crystal\Finance\Core\Exception`

## Stability Commitments

- `Money` never accepts or emits floats.
- `Money::toMinorUnitString()` is the safe minor-unit output API.
- `AssetId` preserves network identity for crypto assets.
- Ledger entries are positive debit or credit facts.
- Ledger transactions must balance per asset, not merely globally.
- Reversals are explicit transactions and cannot be repeated silently.
- Fee breakdown line amounts sum to the final total fee, including minimum and maximum adjustments.
- Idempotency compares canonical payload fingerprints and replays completed records without rerunning callbacks.

## Explicitly Out Of Scope

- Payment intents, payment attempts, authorization/capture/refund/chargeback lifecycles.
- PSP provider contracts, webhooks, payouts, settlement, and reconciliation adapters.
- Persistence adapters, framework service providers, HTTP controllers, queues, reports, taxes, fiscal periods, and UI.

These belong in future packages or application-specific integration layers.
