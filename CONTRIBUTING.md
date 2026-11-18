# Contributing

Thank you for improving Crystal Finance Core.

Before opening a pull request:

```bash
composer qa
composer coverage
```

Coverage and mutation testing require PCOV or Xdebug in the CLI PHP runtime. You can install PCOV with `pecl install pcov`. For Xdebug, use `XDEBUG_MODE=coverage` when running the checks.

For release candidates and high-risk financial changes, also run:

```bash
composer mutation
composer audit
```

Keep changes focused, preserve framework independence, and add tests for every financial invariant. Public API changes must be compatible with `docs/backward-compatibility.md` or be reserved for the next major version.
