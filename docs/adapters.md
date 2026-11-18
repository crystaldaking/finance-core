# Adapter Guide

## Ledger

- Store transactions under unique ids and unique structured references. Use the typed reference fields when the storage schema supports them.
- In one storage transaction, lock the stored original or use an equivalent compare-and-set condition, confirm it remains unreversed, and verify that the reversal offsets its persisted postings. Update only the original's reversal marker and append the reversal. A unique constraint on the reversal's original transaction id is a second line of protection against concurrent attempts.
- Account for entries and asset configuration as stored. Preserve original entry ids, amounts, metadata, and occurrence time. Read reversal state through the typed accessors. Reserve `reversal_of` and `reversed_by` in transaction metadata for the core's compatibility mirrors.
- Assign posting time and an ordering key in storage where the application needs them. Reconstruct balances from entries for the selected cutoff. Choose amount precision and request limits at the ingestion boundary so they match your storage schema. Reject inputs outside those bounds before writing.
- Treat `Asset::isActive()` as an application policy flag. Applications can use it to prevent new business activity while retaining the ability to import, reconcile, or reverse historical entries.

## Idempotency

- Use a unique composite key for `(scope, key)` and acquire or replace an expired claim atomically. A length-prefixed composite encoding is suitable for a string-only store. On completion or failure, compare the current claim id and status atomically before writing.
- Use `PayloadFingerprint::matchesStored()` on the fingerprint computed from the incoming payload. Persist `value()`. Choose a stable, versioned encoding for replay results, including an explicit representation of `null`. Validate decoded results and persist data that can be rehydrated safely.
- For local writes, complete the claim in the same transaction as the business write when they share a consistency boundary. For asynchronous effects, write the outbox entry and mark completion in that transaction, then have the publisher and receiver deduplicate deliveries.
- When the callback calls an external system, pass a durable idempotency key to that system or reconcile its outcome before repeating the call. A completion failure can leave the claim active until its lease expires. A lease and claim id fence the store write and cannot undo an effect that already happened.

## Money and FX

Reject inputs that exceed the adapter's documented precision, size, or encoding policy. Persist minor units without converting to native integers or floating point and store the asset id and scale needed for rehydration. If exchange rates drive recorded conversions, preserve their `asOf` and `source` when available and record the rounding mode used for the conversion.
