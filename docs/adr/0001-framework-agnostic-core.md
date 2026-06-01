# ADR 0001: Framework-Agnostic Core

## Context

The package must be usable from Laravel, Symfony, CLI workers, RoadRunner, Swoole, ReactPHP, and plain PHP.

## Decision

`src/` must not depend on web frameworks, ORMs, queues, HTTP foundations, or service containers.

## Consequences

Integrations are implemented as separate packages.

## Alternatives Considered

A Laravel-first package was rejected because it would make the domain layer harder to reuse.
