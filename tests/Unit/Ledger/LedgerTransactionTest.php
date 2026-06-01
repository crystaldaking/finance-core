<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerAccount;
use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;
use Crystal\Finance\Core\Exception\UnbalancedLedgerTransaction;
use Crystal\Finance\Core\Ledger\Ledger;
use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerEntryId;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerTransactionId;
use Crystal\Finance\Core\Ledger\LedgerTransactionType;
use Crystal\Finance\Core\Ledger\LedgerValidator;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Tests\Fixtures\InMemoryLedgerRepository;
use PHPUnit\Framework\TestCase;

final class LedgerTransactionTest extends TestCase
{
    public function testBalancedTransactionIsAccepted(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('txn_001'),
            id: LedgerTransactionId::fromString('ltx_001'),
        )
            ->debit($this->account('cash'), $this->money('100.00'))
            ->credit($this->account('equity'), $this->money('100.00'));

        (new LedgerValidator())->assertValid($transaction);

        self::assertCount(2, $transaction->entries());
    }

    public function testShortIdentifiersAreAllowedForDeveloperExperience(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('1'),
            id: LedgerTransactionId::fromString('1'),
        )
            ->debit($this->account('cash'), $this->money('1.00'), LedgerEntryId::fromString('1'))
            ->credit($this->account('equity'), $this->money('1.00'), LedgerEntryId::fromString('2'));

        (new LedgerValidator())->assertValid($transaction);

        self::assertSame('manual:1', $transaction->reference()->value());
        self::assertSame('1', $transaction->id()->value());
        self::assertSame('1', $transaction->entries()[0]->id()->value());
    }

    public function testUnbalancedTransactionIsRejected(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('txn_002'),
        )
            ->debit($this->account('cash'), $this->money('100.00'))
            ->credit($this->account('equity'), $this->money('99.99'));

        $this->expectException(UnbalancedLedgerTransaction::class);

        (new LedgerValidator())->assertValid($transaction);
    }

    public function testMultiAssetTransactionMustBalancePerAsset(): void
    {
        $registry = AssetRegistry::default();

        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Journal,
            LedgerReference::manual('txn_003'),
        )
            ->debit($this->account('cash'), Money::of('100.00', 'EUR', $registry))
            ->credit($this->account('equity'), Money::of('100.00', 'EUR', $registry))
            ->debit($this->account('crypto'), Money::of('5.000000', 'USDT@TRON', $registry))
            ->credit($this->account('liability'), Money::of('5.000000', 'USDT@TRON', $registry));

        (new LedgerValidator())->assertValid($transaction);

        self::assertCount(4, $transaction->entries());
    }

    public function testEmptyTransactionIsRejected(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Journal,
            LedgerReference::manual('txn_004'),
        );

        $this->expectException(InvalidLedgerTransaction::class);

        (new LedgerValidator())->assertValid($transaction);
    }

    public function testZeroEntryIsRejected(): void
    {
        $this->expectException(InvalidLedgerTransaction::class);

        $unused = LedgerTransaction::make(
            LedgerTransactionType::Journal,
            LedgerReference::manual('txn_005'),
        )->debit($this->account('cash'), $this->money('0.00'));
    }

    public function testNegativeEntryIsRejected(): void
    {
        $this->expectException(InvalidLedgerTransaction::class);

        $unused = LedgerTransaction::make(
            LedgerTransactionType::Journal,
            LedgerReference::manual('txn_006'),
        )->debit($this->account('cash'), $this->money('-1.00'));
    }

    public function testInvalidAccountIdIsRejected(): void
    {
        $this->expectException(InvalidLedgerAccount::class);

        LedgerAccountId::fromString('Cash Account');
    }

    public function testReverseTransactionFlipsDirectionsAndKeepsAmounts(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('txn_007'),
            id: LedgerTransactionId::fromString('ltx_007'),
        )
            ->debit($this->account('cash'), $this->money('25.00'))
            ->credit($this->account('equity'), $this->money('25.00'));

        $result = $transaction->reverse(
            LedgerReference::manual('txn_007_reversal'),
            LedgerTransactionId::fromString('ltx_007_reversal'),
        );
        $reversal = $result->reversal();
        $original = $result->original();

        (new LedgerValidator())->assertValid($reversal);

        self::assertSame('ltx_007_reversal', $original->reversedBy()?->value());
        self::assertTrue($reversal->isReversal());
        self::assertSame('ltx_007', $reversal->reversalOf()?->value());
        self::assertSame('credit', $reversal->entries()[0]->direction()->value);
        self::assertSame('debit', $reversal->entries()[1]->direction()->value);
        self::assertSame('25.00', $reversal->entries()[0]->amount()->toDecimalString());
    }

    public function testAlreadyReversedTransactionCannotBeMarkedTwice(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('txn_008'),
            id: LedgerTransactionId::fromString('ltx_008'),
        )
            ->debit($this->account('cash'), $this->money('25.00'))
            ->credit($this->account('equity'), $this->money('25.00'))
            ->markReversedBy(LedgerTransactionId::fromString('ltx_008_reversal'));

        $this->expectException(InvalidLedgerTransaction::class);

        $unused = $transaction->markReversedBy(LedgerTransactionId::fromString('ltx_008_second_reversal'));
    }

    public function testMarkedReversedTransactionCannotCreateAnotherReversal(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('txn_010'),
            id: LedgerTransactionId::fromString('ltx_010'),
        )
            ->debit($this->account('cash'), $this->money('25.00'))
            ->credit($this->account('equity'), $this->money('25.00'))
            ->markReversedBy(LedgerTransactionId::fromString('ltx_010_reversal'));

        $this->expectException(InvalidLedgerTransaction::class);

        $unused = $transaction->reverse(LedgerReference::manual('txn_010_second_reversal'));
    }

    public function testEmptyTransactionCannotBeReversed(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Journal,
            LedgerReference::manual('txn_011'),
        );

        $this->expectException(InvalidLedgerTransaction::class);

        $unused = $transaction->reverse(LedgerReference::manual('txn_011_reversal'));
    }

    public function testRepositoryFixtureRejectsDuplicateReferences(): void
    {
        $repository = new InMemoryLedgerRepository();
        $ledger = new Ledger($repository);
        $reference = LedgerReference::manual('txn_009');

        $first = LedgerTransaction::make(LedgerTransactionType::Transfer, $reference)
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'));

        $second = LedgerTransaction::make(LedgerTransactionType::Transfer, $reference)
            ->debit($this->account('cash'), $this->money('2.00'))
            ->credit($this->account('equity'), $this->money('2.00'));

        $ledger->append($first);

        self::assertTrue($repository->existsByReference($reference));

        $this->expectException(InvalidLedgerTransaction::class);

        $ledger->append($second);
    }

    private function money(string $amount): Money
    {
        return Money::of($amount, 'EUR', AssetRegistry::default());
    }

    private function account(string $leaf): LedgerAccountId
    {
        return LedgerAccountId::fromString('test:' . $leaf);
    }
}
