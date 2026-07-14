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

The store interface requires atomic begin semantics. TTLs must be positive. The initial TTL is a processing lease and should exceed the maximum expected callback duration; after completion, the runner starts a fresh replay-retention window of the same length. Store implementations must preserve and compare `IdempotencyRecord::claimId()` during `complete()` and `fail()` so a stale attempt cannot overwrite a newer claim. Redis, SQL, and framework cache implementations belong outside this package.
