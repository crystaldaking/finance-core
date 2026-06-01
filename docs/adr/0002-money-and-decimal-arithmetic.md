# ADR 0002: Money and Decimal Arithmetic

## Context

Financial values require exact decimal behavior.

## Decision

Use `brick/math` `BigDecimal` and explicit rounding for financial calculations. Public APIs accept decimal strings and minor-unit strings.

The package targets `brick/math` `^0.17`, which is compatible with the PHP 8.5 target selected for this core.

## Consequences

No financial domain code may use `float`.

## Alternatives Considered

Native floats were rejected because they cannot represent decimal money safely. Native integers were rejected as the primary minor-unit output because high-precision assets can exceed platform integer limits.
