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
use Crystal\Finance\Core\Ledger\LedgerRepository;
use Crystal\Finance\Core\Ledger\LedgerReversal;
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

    public function testMarkedReversedTransactionCannotBeModified(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('txn_012'),
            id: LedgerTransactionId::fromString('ltx_012'),
        )
            ->debit($this->account('cash'), $this->money('25.00'))
            ->credit($this->account('equity'), $this->money('25.00'))
            ->markReversedBy(LedgerTransactionId::fromString('ltx_012_reversal'));

        $this->expectException(InvalidLedgerTransaction::class);

        $unused = $transaction->debit($this->account('cash'), $this->money('1.00'));
    }

    public function testReversalTransactionCannotBeModified(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('txn_013'),
            id: LedgerTransactionId::fromString('ltx_013'),
        )
            ->debit($this->account('cash'), $this->money('25.00'))
            ->credit($this->account('equity'), $this->money('25.00'));

        $reversal = $transaction->reverse(
            LedgerReference::manual('txn_013_reversal'),
            LedgerTransactionId::fromString('ltx_013_reversal'),
        )->reversal();

        $this->expectException(InvalidLedgerTransaction::class);

        $unused = $reversal->credit($this->account('equity'), $this->money('1.00'));
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

    public function testReversalTypeCannotBeCreatedDirectly(): void
    {
        $this->expectException(InvalidLedgerTransaction::class);

        LedgerTransaction::make(
            LedgerTransactionType::Reversal,
            LedgerReference::manual('invalid_direct_reversal'),
        );
    }

    public function testReversalCannotUseOrdinaryAppendPath(): void
    {
        $repository = $this->plainRepository();
        $ledger = new Ledger($repository);
        $original = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('atomic_original'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'));

        $reversal = $original->reverse(LedgerReference::manual('atomic_reversal'));

        $this->expectException(InvalidLedgerTransaction::class);

        $ledger->append($reversal->reversal());
    }

    public function testLedgerAppendAlwaysValidatesTheTransaction(): void
    {
        $transaction = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('invalid_ledger_append'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('2.00'));

        $this->expectException(UnbalancedLedgerTransaction::class);

        (new Ledger($this->plainRepository()))->append($transaction);
    }

    public function testAtomicReversalUpdatesStoredOriginalAndPreventsSecondReversal(): void
    {
        $repository = new InMemoryLedgerRepository();
        $ledger = new Ledger($repository);
        $originalReference = LedgerReference::manual('persisted_original');
        $reversalReference = LedgerReference::manual('persisted_reversal');
        $original = LedgerTransaction::make(LedgerTransactionType::Transfer, $originalReference)
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'));

        $ledger->append($original);
        $ledger->appendReversal($original->reverse($reversalReference));

        $storedOriginal = $repository->findByReference($originalReference);
        $storedReversal = $repository->findByReference($reversalReference);

        self::assertNotNull($storedOriginal);
        self::assertNotNull($storedReversal);
        $reversedBy = $storedOriginal->reversedBy();
        $reversalOf = $storedReversal->reversalOf();
        self::assertNotNull($reversedBy);
        self::assertNotNull($reversalOf);
        self::assertTrue($storedReversal->id()->equals($reversedBy));
        self::assertTrue($storedOriginal->id()->equals($reversalOf));

        $this->expectException(InvalidLedgerTransaction::class);

        $unused = $storedOriginal->reverse(LedgerReference::manual('persisted_second_reversal'));
    }

    public function testAtomicRepositoryRejectsAStaleSecondReversal(): void
    {
        $repository = new InMemoryLedgerRepository();
        $ledger = new Ledger($repository);
        $original = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('stale_original'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'));
        $first = $original->reverse(LedgerReference::manual('stale_reversal_1'));
        $second = $original->reverse(LedgerReference::manual('stale_reversal_2'));

        $ledger->append($original);
        $ledger->appendReversal($first);

        $this->expectException(InvalidLedgerTransaction::class);

        $ledger->appendReversal($second);
    }

    public function testAtomicReversalRequiresCapableRepository(): void
    {
        $repository = $this->plainRepository();
        $original = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('unsupported_repository_original'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'));

        $this->expectException(InvalidLedgerTransaction::class);

        (new Ledger($repository))->appendReversal(
            $original->reverse(LedgerReference::manual('unsupported_repository_reversal')),
        );
    }

    public function testAtomicReversalValidatesTheOriginalTransaction(): void
    {
        $repository = new InMemoryLedgerRepository();
        $original = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('unbalanced_reversal_original'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('2.00'));

        $this->expectException(UnbalancedLedgerTransaction::class);

        (new Ledger($repository))->appendReversal(
            $original->reverse(LedgerReference::manual('unbalanced_reversal')),
        );
    }

    public function testLedgerReversalRejectsMismatchedOriginalId(): void
    {
        $result = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('pair_original_1'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'))
            ->reverse(LedgerReference::manual('pair_reversal_1'));
        $differentOriginal = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('pair_original_2'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'))
            ->markReversedBy($result->reversal()->id());

        $this->expectException(InvalidLedgerTransaction::class);

        new LedgerReversal($differentOriginal, $result->reversal());
    }

    public function testLedgerReversalRejectsMismatchedReversedById(): void
    {
        $result = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('pair_reversed_by_original'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'))
            ->reverse(LedgerReference::manual('pair_reversed_by_reversal'));
        $originalWithWrongMarker = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('pair_reversed_by_replacement'),
            id: $result->original()->id(),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'))
            ->markReversedBy(LedgerTransactionId::fromString('different_reversal'));

        $this->expectException(InvalidLedgerTransaction::class);

        new LedgerReversal($originalWithWrongMarker, $result->reversal());
    }

    public function testLedgerReversalRejectsEntriesThatDoNotOffsetOriginal(): void
    {
        $result = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('pair_entries_original'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'))
            ->reverse(LedgerReference::manual('pair_entries_reversal'));
        $originalWithDifferentEntries = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('pair_entries_replacement'),
            id: $result->original()->id(),
        )
            ->debit($this->account('cash'), $this->money('2.00'))
            ->credit($this->account('equity'), $this->money('2.00'))
            ->markReversedBy($result->reversal()->id());

        $this->expectException(InvalidLedgerTransaction::class);

        new LedgerReversal($originalWithDifferentEntries, $result->reversal());
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

    private function plainRepository(): LedgerRepository
    {
        return new class () implements LedgerRepository {
            #[\Override]
            public function append(LedgerTransaction $transaction): void
            {
            }

            #[\Override]
            public function findByReference(LedgerReference $reference): ?LedgerTransaction
            {
                return null;
            }

            #[\Override]
            public function existsByReference(LedgerReference $reference): bool
            {
                return false;
            }
        };
    }
}
