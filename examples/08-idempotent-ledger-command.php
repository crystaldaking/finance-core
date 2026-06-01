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
use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerTransactionType;
use Crystal\Finance\Core\Ledger\LedgerValidator;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
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
$key = IdempotencyKey::fromString('request_invoice_12345678');
$payload = [
    'reference' => 'invoice_2026_0001',
    'amount' => '100.00',
    'asset' => 'EUR',
];
$fingerprint = PayloadFingerprint::fromArray($payload);

$postLedgerTransaction = static function () use ($payload): string {
    $registry = AssetRegistry::default();
    $amount = Money::of($payload['amount'], $payload['asset'], $registry);
    $transaction = LedgerTransaction::make(
        LedgerTransactionType::Transfer,
        LedgerReference::of('invoice', $payload['reference']),
    )
        ->debit(LedgerAccountId::fromString('asset:bank:operating'), $amount)
        ->credit(LedgerAccountId::fromString('income:invoices'), $amount);

    (new LedgerValidator())->assertValid($transaction);

    return $transaction->id()->value();
};

$first = $runner->run($scope, $key, $fingerprint, new DateInterval('PT1H'), $postLedgerTransaction);
$second = $runner->run($scope, $key, $fingerprint, new DateInterval('PT1H'), static fn (): string => 'not-used');
$storedResult = $second->result();

if (!is_string($storedResult)) {
    throw new RuntimeException('Expected string result.');
}

echo 'First execution replayed: ' . ($first->replayed() ? 'yes' : 'no') . PHP_EOL;
echo 'Second execution replayed: ' . ($second->replayed() ? 'yes' : 'no') . PHP_EOL;
echo 'Stored transaction id: ' . $storedResult . PHP_EOL;
