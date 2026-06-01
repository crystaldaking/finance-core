<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Invariants;

use Brick\Math\BigInteger;
use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;
use Crystal\Finance\Core\Exception\UnbalancedLedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerDirection;
use Crystal\Finance\Core\Ledger\LedgerEntry;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerTransactionId;
use Crystal\Finance\Core\Ledger\LedgerTransactionType;
use Crystal\Finance\Core\Ledger\LedgerValidator;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use PHPUnit\Framework\TestCase;

final class LedgerInvariantTest extends TestCase
{
    public function testBalancedTransactionsAreAcceptedAcrossAssetsAndEntryShapes(): void
    {
        foreach ($this->balancedTransactions() as $transaction) {
            (new LedgerValidator())->assertValid($transaction);

            self::assertNotEmpty($transaction->entries());
        }
    }

    public function testOneMinorUnitMutationBreaksPerAssetBalance(): void
    {
        $positiveAmounts = $this->positiveAmounts();
        $rejections = 0;

        foreach ($positiveAmounts as [$assetId, $minorUnits]) {
            $amount = Money::ofMinor($minorUnits, $assetId, $this->registry());
            $mutatedCredit = Money::ofMinor(BigInteger::of($minorUnits)->minus(1), $assetId, $this->registry());
            $transaction = LedgerTransaction::make(
                LedgerTransactionType::Transfer,
                LedgerReference::manual('unbalanced_' . str_replace('@', '_', strtolower($assetId))),
            )
                ->debit($this->account('cash'), $amount)
                ->credit($this->account('equity'), $mutatedCredit);

            try {
                (new LedgerValidator())->assertValid($transaction);
                self::fail('Expected unbalanced transaction for ' . $assetId);
            } catch (UnbalancedLedgerTransaction) {
                $rejections++;
            }
        }

        self::assertSame(count($positiveAmounts), $rejections);
    }

    public function testReversalBalancesAndOffsetsEveryOriginalAccountEntry(): void
    {
        $original = LedgerTransaction::make(
            LedgerTransactionType::Journal,
            LedgerReference::manual('compound_original'),
            id: LedgerTransactionId::fromString('compound_original'),
        )
            ->debit($this->account('cash'), $this->money('70.00'))
            ->debit($this->account('receivable'), $this->money('30.00'))
            ->credit($this->account('revenue'), $this->money('100.00'));

        $result = $original->reverse(
            LedgerReference::manual('compound_reversal'),
            LedgerTransactionId::fromString('compound_reversal'),
        );

        (new LedgerValidator())->assertValid($result->reversal());

        $combinedBalances = $this->signedBalances([
            ...$original->entries(),
            ...$result->reversal()->entries(),
        ]);

        foreach ($combinedBalances as $balance) {
            self::assertSame('0', $balance->toString());
        }
    }

    public function testReversalTransactionsCannotBeReversedAgain(): void
    {
        $original = LedgerTransaction::make(
            LedgerTransactionType::Transfer,
            LedgerReference::manual('single_reversal_original'),
        )
            ->debit($this->account('cash'), $this->money('1.00'))
            ->credit($this->account('equity'), $this->money('1.00'));

        $reversal = $original->reverse(LedgerReference::manual('single_reversal'))->reversal();

        $this->expectException(InvalidLedgerTransaction::class);

        $unused = $reversal->reverse(LedgerReference::manual('single_reversal_second'));
    }

    /**
     * @return list<LedgerTransaction>
     */
    private function balancedTransactions(): array
    {
        return [
            LedgerTransaction::make(LedgerTransactionType::Transfer, LedgerReference::manual('balanced_eur'))
                ->debit($this->account('cash'), $this->money('100.00'))
                ->credit($this->account('equity'), $this->money('100.00')),
            LedgerTransaction::make(LedgerTransactionType::Journal, LedgerReference::manual('balanced_split'))
                ->debit($this->account('cash'), $this->money('70.00'))
                ->debit($this->account('receivable'), $this->money('30.00'))
                ->credit($this->account('revenue'), $this->money('100.00')),
            LedgerTransaction::make(LedgerTransactionType::Journal, LedgerReference::manual('balanced_multi_asset'))
                ->debit($this->account('cash'), $this->money('10.00'))
                ->credit($this->account('equity'), $this->money('10.00'))
                ->debit($this->account('treasury'), Money::of('1.000000', 'USDT@TRON', $this->registry()))
                ->credit($this->account('liability'), Money::of('1.000000', 'USDT@TRON', $this->registry())),
        ];
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function positiveAmounts(): array
    {
        return [
            ['EUR', '100'],
            ['USDT@TRON', '1000000'],
            ['ETH@ETHEREUM', '1000000000000000000'],
        ];
    }

    /**
     * @param list<LedgerEntry> $entries
     * @return array<string, BigInteger>
     */
    private function signedBalances(array $entries): array
    {
        $balances = [];

        foreach ($entries as $entry) {
            $key = $entry->accountId()->value() . ':' . $entry->amount()->asset()->id()->value();
            $signedMinorUnits = BigInteger::of($entry->amount()->toMinorUnitString());

            if ($entry->direction() === LedgerDirection::Credit) {
                $signedMinorUnits = $signedMinorUnits->negated();
            }

            $balances[$key] = ($balances[$key] ?? BigInteger::zero())->plus($signedMinorUnits);
        }

        return $balances;
    }

    private function money(string $amount): Money
    {
        return Money::of($amount, 'EUR', $this->registry());
    }

    private function account(string $leaf): LedgerAccountId
    {
        return LedgerAccountId::fromString('invariant:' . $leaf);
    }

    private function registry(): AssetRegistry
    {
        return AssetRegistry::default();
    }
}
