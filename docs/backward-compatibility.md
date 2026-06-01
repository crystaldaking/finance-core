# Backward Compatibility Policy

`v1.0.0` is the first semver-stable release.

## Public API

The public API is every non-internal class, enum, interface, method, constructor, static factory, exception, and documented behavior under `Crystal\Finance\Core`.

Backward-compatible changes:

- Adding a new class, enum case, method, optional parameter, or exception subtype.
- Adding support for a new asset in `AssetRegistry::default()` when it does not alter existing assets.
- Strengthening documentation, tests, examples, or static analysis without changing runtime behavior.
- Making validation errors more specific while preserving the parent exception type.

Backward-incompatible changes:

- Removing or renaming public symbols.
- Changing method signatures, return types, accepted value formats, or exception parent types.
- Changing money scale behavior, rounding behavior, ledger balancing semantics, fee calculation semantics, or idempotency replay semantics.
- Reinterpreting asset ids, especially network-aware crypto ids.
- Adding framework, database, queue, HTTP, or service container dependencies to `src/`.

## Release Rules

- Patch releases may contain bug fixes and documentation improvements only.
- Minor releases may add public API without breaking existing behavior.
- Major releases may break public API, but must include migration notes.
- Anything not ready for long-term support should stay out of the public namespace.
