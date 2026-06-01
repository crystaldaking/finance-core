# Idempotency Example

Use an idempotency key, scope, and canonical payload fingerprint to make retryable commands safe.

```php
use Crystal\Finance\Core\Idempotency\IdempotencyKey;
use Crystal\Finance\Core\Idempotency\IdempotencyScope;
use Crystal\Finance\Core\Idempotency\PayloadFingerprint;

$scope = IdempotencyScope::fromString('ledger:append');
$key = IdempotencyKey::fromString('request_12345678');
$fingerprint = PayloadFingerprint::fromArray([
    'reference' => 'transfer_123',
    'amount' => '100.00',
    'asset' => 'EUR',
]);
```

The store interface requires atomic begin semantics. Redis, SQL, and framework cache implementations belong outside this package.
