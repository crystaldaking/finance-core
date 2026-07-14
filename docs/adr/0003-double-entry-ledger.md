# ADR 0003: Double-Entry Ledger

## Context

Financial applications need auditable movement of value.

## Decision

The core exposes append-only double-entry ledger transactions that balance per asset. Ledger account ids are opaque validated identifiers; asset identity comes from `Money`, not from account string parsing.

Reversals can only be created from an existing transaction. They are persisted through `Ledger::appendReversal()` and `LedgerReversalRepository`, whose implementation must atomically mark the stored original and append exactly one reversal.

## Consequences

Balances are derived from entries. Persistence adapters must preserve append-only behavior. Reversal adapters must enforce uniqueness by original transaction id in storage, not only in memory.

## Alternatives Considered

Stored mutable balances were rejected as a core primitive because they are harder to audit.
