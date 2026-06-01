# ADR 0003: Double-Entry Ledger

## Context

Financial applications need auditable movement of value.

## Decision

The core exposes append-only double-entry ledger transactions that balance per asset.

## Consequences

Balances are derived from entries. Persistence adapters must preserve append-only behavior.

## Alternatives Considered

Stored mutable balances were rejected as a core primitive because they are harder to audit.
