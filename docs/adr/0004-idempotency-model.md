# ADR 0004: Idempotency Model

## Context

Financial commands are commonly retried.

## Decision

The core provides idempotency keys, scopes, canonical payload fingerprints, records, runner behavior, and storage contracts. Stores must implement atomic begin semantics for active records.

Expired records are claimable again. A concrete store may hide expired records from `find()` or atomically replace them during `begin()`.

TTLs must resolve to a positive duration. The initial expiry acts as the processing lease and must cover the expected callback runtime. After successful completion, the runner refreshes expiry so the full replay-retention TTL starts when the callback finishes. Each attempt has a unique `claimId`; stores must compare it and atomically reject stale completion or failure attempts when a newer claim already exists.

## Consequences

Redis, SQL, and framework cache implementations live outside the core.

## Alternatives Considered

Bundling a concrete store was rejected to preserve framework independence. Serializing arbitrary command results inside the core was rejected; adapters should decide what can be stored safely.
