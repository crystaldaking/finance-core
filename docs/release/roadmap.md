# v2 Roadmap

- Decide the exception hierarchy and conversion error contract. Include migration examples for consumers that catch `UnbalancedLedgerTransaction`, `InvalidLedgerTransaction` or Brick rounding exceptions.
- Decide which time fields must be mandatory and define ordering scope for a persisted sequence. Provide a backfill strategy for historical rows before requiring those fields.
- Evaluate a typed asset identifier and whether to retire redundant APIs. Review external adapter usage of `LedgerTransactionRunner` and other public methods before removing them.
- Introduce an explicitly versioned persisted fingerprint with a migration path for active v1 records.
- Consider opt-in ingress limits for identifiers, amounts, and metadata. Any default that rejects valid historical data needs a transition plan. Define strict scalar input handling at the public boundary for callers using PHP coercion.
