# Examples

Each example is executable:

```bash
php examples/06-platform-fee-ledger-posting.php
```

## Scenarios

- `01-money-and-network-aware-assets.php` shows explicit asset boundaries, high-precision minor units, and why `USDT@TRON` is not the same asset as `USDT@ETHEREUM`.
- `02-cost-center-allocation.php` allocates one invoice across cost centers while preserving the exact total.
- `03-corporate-expense-journal.php` posts a simple corporate expense journal.
- `04-multi-asset-treasury-ledger.php` posts a transaction that balances independently for EUR and USDT on Tron.
- `05-service-fee-calculation.php` calculates invoice fees with percentage, fixed, minimum, maximum, and explainable breakdown lines.
- `06-platform-fee-ledger-posting.php` is the end-to-end merchant collection flow: fee calculation, ledger posting, audit context, domain event, and idempotency fingerprint.
- `07-reversal-correction.php` creates a reversal transaction instead of mutating an already-posted ledger fact.
- `08-idempotent-ledger-command.php` wraps ledger posting in idempotency primitives and demonstrates replay behavior.
- `09-exchange-rate-conversion.php` converts money through an explicit asset pair and rate.
- `10-audit-and-domain-events.php` records a domain event for outbox-style application integrations.
