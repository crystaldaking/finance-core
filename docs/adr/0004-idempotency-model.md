# ADR 0004: Idempotency Model

## Context

Financial commands are commonly retried.

## Decision

The core provides idempotency keys, scopes, canonical payload fingerprints, records, runner behavior, and storage contracts. Stores must implement atomic begin semantics for active records.

Expired records are claimable again. A concrete store may hide expired records from `find()` or atomically replace them during `begin()`.

Store identities consist of both scope and key. Implementations should use separate unique-index columns or an unambiguous encoding for the pair.

TTLs must resolve to a positive duration. The initial expiry acts as the processing lease and must cover the expected callback runtime. After successful completion, the runner refreshes expiry so the full replay-retention TTL starts when the callback finishes. Each attempt has a unique `claimId`; stores must compare it and atomically reject stale completion or failure attempts when a newer claim already exists.

If the callback succeeds and completion fails before being persisted, a new claim can execute the callback after the lease expires. Applications coordinate this boundary as described in the [adapter guide](../adapters.md).

The stored fingerprint remains `sha256:<64 lowercase hex digits>`. Lists retain order and maps use bytewise string ordering as of v1.2. During replay, an incoming fingerprint derived from a payload can also match the v1.1 digest of that payload in its incoming field order. This preserves existing records when retried with the original ordering. An explicit version in the persisted format requires a coordinated migration.

## Consequences

Redis, SQL, and framework cache implementations live outside the core.

## Alternatives Considered

Bundling a concrete store was rejected to preserve framework independence. Serializing arbitrary command results inside the core was rejected; adapters should decide what can be stored safely.
