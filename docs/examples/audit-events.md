# Audit and Events Example

```php
use Crystal\Finance\Core\Audit\Actor;
use Crystal\Finance\Core\Audit\AuditContext;
use Crystal\Finance\Core\Event\EventMetadata;
use Crystal\Finance\Core\Event\GenericDomainEvent;

$audit = AuditContext::record(
    Actor::system(),
    'Nightly reconciliation created a correction transaction',
    new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
);

$event = GenericDomainEvent::record(
    'Ledger.TransactionPosted',
    'ltx_123',
    new DateTimeImmutable('2026-01-01T00:00:00+00:00'),
    EventMetadata::fromArray(['source' => 'reconciliation']),
    $audit,
);
```

The core records domain events but never dispatches them.
