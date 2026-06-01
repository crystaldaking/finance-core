# ADR 0002: Money and Decimal Arithmetic

## Context

Financial values require exact decimal behavior.

## Decision

Use `brick/math` `BigDecimal` and explicit rounding for financial calculations. Public APIs accept decimal strings and minor-unit strings.

## Consequences

No financial domain code may use `float`.

## Alternatives Considered

Native floats were rejected because they cannot represent decimal money safely.
