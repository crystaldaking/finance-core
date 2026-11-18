# ADR 0002: Money and Decimal Arithmetic

## Context

Financial values require exact decimal behavior.

## Decision

Use `brick/math` `BigDecimal` and explicit rounding for financial calculations. Public APIs accept decimal strings and minor-unit strings.

The package supports `brick/math` `^0.17 || ^1.0`. PHP 8.5 is the runtime baseline described in [ADR 0005](0005-php-runtime-baseline.md).

Amounts parsed with `Money::of()` must fit the asset scale without rounding. Calculations that can require rounding take an explicit `RoundingMode`. Allocation works on absolute minor units, divides each share using integer quotients and gives remaining units to the first shares in input order. Negative totals use the same allocation and restore the sign at the end.

## Consequences

No financial domain code may use `float`.

## Alternatives Considered

Native floats were rejected because they cannot represent decimal money safely. Native integers were rejected as the primary minor-unit output because high-precision assets can exceed platform integer limits.
