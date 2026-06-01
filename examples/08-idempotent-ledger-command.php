<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Crystal\Finance\Core\Exception\IdempotencyConflict;
use Crystal\Finance\Core\Idempotency\IdempotencyKey;
use Crystal\Finance\Core\Idempotency\IdempotencyRecord;
use Crystal\Finance\Core\Idempotency\IdempotencyRunner;
use Crystal\Finance\Core\Idempotency\IdempotencyScope;
use Crystal\Finance\Core\Idempotency\IdempotencyStatus;
use Crystal\Finance\Core\Idempotency\IdempotencyStore;
use Crystal\Finance\Core\Idempotency\PayloadFingerprint;
use Psr\Clock\ClockInterface;

final class ExampleIdempotencyClock implements ClockInterface
{
    #[\Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-01T00:00:00+00:00');
    }
}

final class ExampleIdempotencyStore implements IdempotencyStore
{
    /**
     * @var array<string, IdempotencyRecord>
     */
    private array $records = [];

    #[\Override]
    public function find(IdempotencyScope $scope, IdempotencyKey $key): ?IdempotencyRecord
    {
        return $this->records[$this->recordKey($scope, $key)] ?? null;
    }

    #[\Override]
    public function begin(
        IdempotencyScope $scope,
        IdempotencyKey $key,
        PayloadFingerprint $fingerprint,
        DateTimeImmutable $expiresAt,
    ): IdempotencyRecord {
        $recordKey = $this->recordKey($scope, $key);

        if (isset($this->records[$recordKey])) {
            throw IdempotencyConflict::alreadyStarted($scope->value(), $key->value());
        }

        $record = new IdempotencyRecord($scope, $key, $fingerprint, IdempotencyStatus::Started, $expiresAt);
        $this->records[$recordKey] = $record;

        return $record;
    }

    #[\Override]
    public function complete(IdempotencyRecord $record, mixed $result): IdempotencyRecord
    {
        $completed = new IdempotencyRecord(
            $record->scope(),
            $record->key(),
            $record->fingerprint(),
            IdempotencyStatus::Completed,
            $record->expiresAt(),
            $result,
        );
        $this->records[$this->recordKey($record->scope(), $record->key())] = $completed;

        return $completed;
    }

    #[\Override]
    public function fail(IdempotencyRecord $record, Throwable $throwable): IdempotencyRecord
    {
        $failed = new IdempotencyRecord(
            $record->scope(),
            $record->key(),
            $record->fingerprint(),
            IdempotencyStatus::Failed,
            $record->expiresAt(),
            null,
            $throwable::class,
        );
        $this->records[$this->recordKey($record->scope(), $record->key())] = $failed;

        return $failed;
    }

    private function recordKey(IdempotencyScope $scope, IdempotencyKey $key): string
    {
        return $scope->value() . ':' . $key->value();
    }
}

$runner = new IdempotencyRunner(new ExampleIdempotencyStore(), new ExampleIdempotencyClock());
$scope = IdempotencyScope::fromString('ledger:append');
$key = IdempotencyKey::fromString('request_12345678');
$fingerprint = PayloadFingerprint::fromArray(['reference' => 'transfer_123', 'amount' => '100.00']);

$first = $runner->run($scope, $key, $fingerprint, new DateInterval('PT1H'), static fn (): string => 'posted');
$second = $runner->run($scope, $key, $fingerprint, new DateInterval('PT1H'), static fn (): string => 'not-used');
$storedResult = $second->result();

if (!is_string($storedResult)) {
    throw new RuntimeException('Expected string result.');
}

echo 'First execution replayed: ' . ($first->replayed() ? 'yes' : 'no') . PHP_EOL;
echo 'Second execution replayed: ' . ($second->replayed() ? 'yes' : 'no') . PHP_EOL;
echo 'Stored result: ' . $storedResult . PHP_EOL;
