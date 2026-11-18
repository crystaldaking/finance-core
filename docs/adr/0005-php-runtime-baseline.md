# ADR 0005 — PHP runtime baseline

## Context

Finance Core targets new PHP applications. The baseline influences which runtime guarantees callers can rely on.

## Decision

Require PHP 8.5 or later within the PHP 8 series. Use `NoDiscard` on immutable operations where dropping a return value is likely to lose a requested change.

## Verification

PHP 8.5 is the required CI runtime for highest and lowest supported dependency versions. The runtime smoke check also exercises PHP 8.6 prereleases while their ecosystem matures.
