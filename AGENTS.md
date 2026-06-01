# Agent Notes

This repository is a framework-agnostic PHP financial core.

- Keep `src/` free of framework dependencies.
- Never use `float` for financial calculations.
- Prefer immutable value objects and explicit invariants.
- Run `composer qa` before committing release milestones.
- Payment provider adapters, webhooks, Laravel integration, reports, taxes, and persistence adapters are out of scope for `finance-core` v1.
