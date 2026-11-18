# ADR 0003: Double-Entry Ledger

## Context

Financial applications need auditable movement of value.

## Decision

The core exposes append-only double-entry ledger transactions that balance per asset. Ledger account ids are opaque validated identifiers; asset identity comes from `Money`, not from account string parsing.

Reversals are derived from original transactions and persisted through `Ledger::appendReversal()` and `LedgerReversalRepository`. The adapter atomically marks the stored original and appends exactly one reversal that offsets the stored entries. The operation preserves the original postings, timestamps, and application metadata.

## Consequences

Balances are derived from entries. Persistence adapters must preserve append-only behavior. Reversal adapters must enforce uniqueness by original transaction id in storage, not only in memory.

`LedgerTransaction::occurredAt()` represents the business occurrence time when supplied. Storage can record its own posting time or sequence. `LedgerBalance::asOf()` describes the point in time of a derived balance supplied by the caller.

## Alternatives Considered

Stored mutable balances were rejected as a core primitive because they are harder to audit.
