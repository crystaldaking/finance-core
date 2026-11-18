<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Invariants;

use Crystal\Finance\Core\Audit\Actor;
use Crystal\Finance\Core\Audit\CausationId;
use Crystal\Finance\Core\Audit\CorrelationId;
use Crystal\Finance\Core\Audit\RequestId;
use Crystal\Finance\Core\Event\EventId;
use Crystal\Finance\Core\Event\GenericDomainEvent;
use Crystal\Finance\Core\Exception\InvalidAllocation;
use Crystal\Finance\Core\Exception\InvalidAssetCode;
use Crystal\Finance\Core\Exception\InvalidAuditContext;
use Crystal\Finance\Core\Exception\InvalidDomainEvent;
use Crystal\Finance\Core\Exception\InvalidIdempotencyKey;
use Crystal\Finance\Core\Exception\InvalidIdempotencyScope;
use Crystal\Finance\Core\Exception\InvalidLedgerAccount;
use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;
use Crystal\Finance\Core\Exception\InvalidMoneyAmount;
use Crystal\Finance\Core\Exception\InvalidNetwork;
use Crystal\Finance\Core\Exception\InvalidPayloadFingerprint;
use Crystal\Finance\Core\Exception\InvalidPercentage;
use Crystal\Finance\Core\Exception\InvalidRate;
use Crystal\Finance\Core\Idempotency\IdempotencyKey;
use Crystal\Finance\Core\Idempotency\IdempotencyScope;
use Crystal\Finance\Core\Idempotency\PayloadFingerprint;
use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerEntryId;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransactionId;
use Crystal\Finance\Core\Money\AssetCode;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Network;
use Crystal\Finance\Core\Money\Percentage;
use Crystal\Finance\Core\Money\Rate;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

final class StringValidationTest extends TestCase
{
    /**
     * @param callable(string): (object|list<Money>) $validate
     * @param class-string<Throwable> $exception
     */
    #[DataProvider('invalidValues')]
    public function testRejectsCharactersOutsideTheFormat(callable $validate, string $value, string $exception): void
    {
        $this->expectException($exception);

        $unused = $validate($value);
    }

    /**
     * @return iterable<string, array{callable(string): (object|list<Money>), string, class-string<Throwable>}>
     */
    public static function invalidValues(): iterable
    {
        $time = new DateTimeImmutable('2026-01-01T00:00:00Z');
        $cases = [
            'actor' => [Actor::system(...), 'worker', InvalidAuditContext::class],
            'causation id' => [CausationId::fromString(...), 'request', InvalidAuditContext::class],
            'correlation id' => [CorrelationId::fromString(...), 'request', InvalidAuditContext::class],
            'request id' => [RequestId::fromString(...), 'request', InvalidAuditContext::class],
            'event id' => [EventId::fromString(...), 'event', InvalidDomainEvent::class],
            'event name' => [static fn (string $value): GenericDomainEvent => GenericDomainEvent::record($value, 'ledger', $time), 'Ledger.Posted', InvalidDomainEvent::class],
            'aggregate id' => [static fn (string $value): GenericDomainEvent => GenericDomainEvent::record('Ledger.Posted', $value, $time), 'ledger', InvalidDomainEvent::class],
            'idempotency key' => [IdempotencyKey::fromString(...), 'request01', InvalidIdempotencyKey::class],
            'idempotency scope' => [IdempotencyScope::fromString(...), 'ledger:post', InvalidIdempotencyScope::class],
            'fingerprint' => [PayloadFingerprint::fromString(...), 'sha256:' . str_repeat('a', 64), InvalidPayloadFingerprint::class],
            'account id' => [LedgerAccountId::fromString(...), 'cash', InvalidLedgerAccount::class],
            'entry id' => [LedgerEntryId::fromString(...), 'entry', InvalidLedgerTransaction::class],
            'transaction id' => [LedgerTransactionId::fromString(...), 'tx', InvalidLedgerTransaction::class],
            'reference type' => [static fn (string $value): LedgerReference => LedgerReference::of($value, 'tx'), 'manual', InvalidLedgerTransaction::class],
            'reference id' => [LedgerReference::manual(...), 'tx', InvalidLedgerTransaction::class],
            'asset code' => [AssetCode::fromString(...), 'EUR', InvalidAssetCode::class],
            'network' => [Network::fromString(...), 'TRON', InvalidNetwork::class],
            'amount' => [static fn (string $value): Money => Money::of($value, 'EUR'), '10.25', InvalidMoneyAmount::class],
            'minor units' => [static fn (string $value): Money => Money::ofMinor($value, 'EUR'), '100', InvalidMoneyAmount::class],
            'ratio' => [static fn (string $value): array => Money::of('10', 'EUR')->allocate([$value]), '1', InvalidAllocation::class],
            'percentage' => [Percentage::of(...), '2.5', InvalidPercentage::class],
            'rate' => [Rate::of(...), '1.05', InvalidRate::class],
        ];

        foreach ($cases as $name => [$validate, $value, $exception]) {
            yield $name . ' leading LF' => [$validate, "\n" . $value, $exception];

            foreach (['LF' => "\n", 'CRLF' => "\r\n", 'CR' => "\r", 'space' => ' ', 'NUL' => "\0"] as $suffix => $character) {
                yield $name . ' trailing ' . $suffix => [$validate, $value . $character, $exception];
            }
        }
    }
}
