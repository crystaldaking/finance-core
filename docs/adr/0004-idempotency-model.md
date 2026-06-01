# ADR 0004: Idempotency Model

## Context

Financial commands are commonly retried.

## Decision

The core provides idempotency keys, scopes, canonical payload fingerprints, records, runner behavior, and storage contracts. Stores must implement atomic begin semantics.

## Consequences

Redis, SQL, and framework cache implementations live outside the core.

## Alternatives Considered

Bundling a concrete store was rejected to preserve framework independence. Serializing arbitrary command results inside the core was rejected; adapters should decide what can be stored safely.
