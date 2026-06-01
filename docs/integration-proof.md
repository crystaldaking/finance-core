# Integration Proof

The executable script at `demo/merchant-finance-flow.php` demonstrates the core working as a small merchant finance flow:

- accepts a network-aware crypto gross amount;
- calculates a generic platform fee;
- posts a balanced double-entry ledger transaction;
- attaches audit context and a domain event;
- builds an idempotency fingerprint for retry-safe command handling.

Run it with:

```bash
php demo/merchant-finance-flow.php
```

The demo is intentionally storage-free. Applications provide their own repositories, locks, serialization, queues, and framework integration around these primitives.
