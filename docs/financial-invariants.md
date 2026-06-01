# Financial Invariants

- Money values never use floating point arithmetic.
- Money operations require matching assets.
- Asset identifiers distinguish network-specific crypto assets.
- Ledger transactions cannot be empty.
- Ledger entries must be positive.
- Ledger transactions must balance per asset.
- Reversals are explicit and cannot be silently repeated.
- Fee calculations are deterministic and explainable.
- Idempotency keys cannot be reused with different payload fingerprints.
