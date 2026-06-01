# Release Checklist

Use this checklist before creating or moving a release tag.

## Required Gates

```bash
composer qa
composer coverage
composer mutation
composer audit
```

## Manual Review

- Confirm `CHANGELOG.md` has the target version and date.
- Confirm `README.md`, examples, and docs match the shipped public API.
- Confirm no payment-specific lifecycle API leaked into `finance-core`.
- Confirm `src/` has no framework, storage, HTTP, queue, or clock implementation dependency beyond PSR-20 interfaces.
- Confirm public API changes are compatible with `docs/backward-compatibility.md`.
- Confirm generated artifacts such as `build/`, `.phpunit.cache/`, `.php-cs-fixer.cache`, `vendor/`, and `composer.lock` are not committed.

## Tagging

```bash
git tag -a v1.0.0 -m "v1.0.0"
```

Use annotated tags. Move a local release tag only before it has been pushed.
