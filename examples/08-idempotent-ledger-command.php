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

    public function __construct(private ClockInterface $clock)
    {
    }

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
        $existing = $this->records[$recordKey] ?? null;

        if ($existing !== null && !$existing->isExpired($this->clock->now())) {
            if (!$existing->fingerprint()->equals($fingerprint)) {
                throw IdempotencyConflict::fingerprintMismatch($scope->value(), $key->value());
            }

            throw IdempotencyConflict::alreadyStarted($scope->value(), $key->value());
        }

        $record = new IdempotencyRecord($scope, $key, $fingerprint, IdempotencyStatus::Started, $expiresAt);
        $this->records[$recordKey] = $record;

        return $record;
    }

    #[\Override]
    public function complete(IdempotencyRecord $record, mixed $result): IdempotencyRecord
    {
        $this->assertActiveClaim($record);

        $completed = new IdempotencyRecord(
            $record->scope(),
            $record->key(),
            $record->fingerprint(),
            IdempotencyStatus::Completed,
            $record->expiresAt(),
            $result,
            claimId: $record->claimId(),
        );
        $this->records[$this->recordKey($record->scope(), $record->key())] = $completed;

        return $completed;
    }

    #[\Override]
    public function fail(IdempotencyRecord $record, Throwable $throwable): IdempotencyRecord
    {
        $this->assertActiveClaim($record);

        $failed = new IdempotencyRecord(
            $record->scope(),
            $record->key(),
            $record->fingerprint(),
            IdempotencyStatus::Failed,
            $record->expiresAt(),
            null,
            $throwable::class,
            $record->claimId(),
        );
        $this->records[$this->recordKey($record->scope(), $record->key())] = $failed;

        return $failed;
    }

    private function recordKey(IdempotencyScope $scope, IdempotencyKey $key): string
    {
        return $scope->value() . ':' . $key->value();
    }

    private function assertActiveClaim(IdempotencyRecord $record): void
    {
        $current = $this->records[$this->recordKey($record->scope(), $record->key())] ?? null;

        if ($current === null
            || $current->status() !== IdempotencyStatus::Started
            || !hash_equals($current->claimId(), $record->claimId())
        ) {
            throw IdempotencyConflict::staleClaim($record->scope()->value(), $record->key()->value());
        }
    }
}

$clock = new ExampleIdempotencyClock();
$runner = new IdempotencyRunner(new ExampleIdempotencyStore($clock), $clock);
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
