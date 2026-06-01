# API Stability

`v1.0.0` is the first semver-stable release of `crystaldaking/finance-core`.

Stable public surface:

- `Crystal\Finance\Core\Money`
- `Crystal\Finance\Core\Ledger`
- `Crystal\Finance\Core\Fee`
- `Crystal\Finance\Core\Audit`
- `Crystal\Finance\Core\Event`
- `Crystal\Finance\Core\Idempotency`
- `Crystal\Finance\Core\Exception`

Future payment-specific APIs must be added to `crystaldaking/payments-core` unless they are truly generic financial primitives.
