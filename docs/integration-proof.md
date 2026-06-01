# Integration Proof

The executable script at `examples/06-platform-fee-ledger-posting.php` demonstrates the core working as a small merchant finance flow:

- accepts a network-aware crypto gross amount;
- calculates a generic platform fee;
- posts a balanced double-entry ledger transaction;
- attaches audit context and a domain event;
- builds an idempotency fingerprint for retry-safe command handling.

Run it with:

```bash
php examples/06-platform-fee-ledger-posting.php
```

The example is intentionally storage-free. Applications provide their own repositories, locks, serialization, queues, and framework integration around these primitives.
