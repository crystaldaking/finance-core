# Financial Invariants

- Money values never use floating point arithmetic.
- Money operations require matching assets.
- Asset identifiers distinguish network-specific crypto assets.
- Decimal scale is enforced by the asset.
- Minor units are exposed as strings to avoid overflow for high-precision assets.
- Ledger transactions cannot be empty.
- Ledger entries must be positive.
- Ledger transactions must balance per asset.
- Reversals are explicit and must be persisted atomically with their marked original transaction.
- Fee calculations are deterministic and explainable.
- Fee calculations reject incompatible assets.
- Fee calculations reject negative gross amounts.
- Fee calculations cannot make net negative unless explicitly allowed.
- Idempotency keys cannot be reused with different payload fingerprints.
- Idempotency TTLs must be positive, and completed replay retention starts when the callback finishes.
- Payload fingerprints are canonicalized so object key order does not affect identity.
