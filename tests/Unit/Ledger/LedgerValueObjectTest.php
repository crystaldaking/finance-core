<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;
use Crystal\Finance\Core\Ledger\LedgerAccountId;
use Crystal\Finance\Core\Ledger\LedgerBalance;
use Crystal\Finance\Core\Ledger\LedgerEntryId;
use Crystal\Finance\Core\Ledger\LedgerMetadata;
use Crystal\Finance\Core\Ledger\LedgerReference;
use Crystal\Finance\Core\Ledger\LedgerTransactionId;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\Money;
use PHPUnit\Framework\TestCase;

final class LedgerValueObjectTest extends TestCase
{
    public function testMetadataIsImmutableAndReadable(): void
    {
        $metadata = LedgerMetadata::fromArray(['source' => 'import', 'verified' => true])
            ->with('line', 10);

        self::assertSame('import', $metadata->get('source'));
        self::assertTrue($metadata->get('verified'));
        self::assertSame(10, $metadata->get('line'));
        self::assertNull($metadata->get('missing'));
        self::assertSame(['source' => 'import', 'verified' => true, 'line' => 10], $metadata->toArray());
    }

    public function testReferenceExposesPartsEqualityAndStringValue(): void
    {
        $reference = LedgerReference::of('invoice', 'INV-2026:001');
        $same = LedgerReference::of('invoice', 'INV-2026:001');
        $different = LedgerReference::of('invoice', 'INV-2026:002');

        self::assertSame('invoice', $reference->type());
        self::assertSame('INV-2026:001', $reference->id());
        self::assertSame('invoice:INV-2026:001', $reference->value());
        self::assertSame('invoice:INV-2026:001', (string) $reference);
        self::assertTrue($reference->equals($same));
        self::assertFalse($reference->equals($different));
    }

    public function testReferenceRejectsInvalidType(): void
    {
        $this->expectException(InvalidLedgerTransaction::class);

        LedgerReference::of('Invoice', 'INV-1');
    }

    public function testReferenceRejectsInvalidIdentifier(): void
    {
        $this->expectException(InvalidLedgerTransaction::class);

        LedgerReference::of('invoice', 'bad id');
    }

    public function testLedgerIdentifiersGenerateCompareAndCastToString(): void
    {
        $generatedTransactionId = LedgerTransactionId::generate();
        $transactionId = LedgerTransactionId::fromString('ltx_manual_1');
        $sameTransactionId = LedgerTransactionId::fromString('ltx_manual_1');
        $entryId = LedgerEntryId::fromString('le_manual_1');
        $generatedEntryId = LedgerEntryId::generate();

        self::assertStringStartsWith('ltx_', $generatedTransactionId->value());
        self::assertTrue($transactionId->equals($sameTransactionId));
        self::assertSame('ltx_manual_1', (string) $transactionId);
        self::assertSame('le_manual_1', (string) $entryId);
        self::assertStringStartsWith('le_', $generatedEntryId->value());
    }

    public function testLedgerBalanceCarriesAccountAndMoney(): void
    {
        $accountId = LedgerAccountId::fromString('asset:cash');
        $money = Money::of('12.34', 'EUR', AssetRegistry::default());
        $balance = new LedgerBalance($accountId, $money);

        self::assertTrue($accountId->equals($balance->accountId()));
        self::assertSame('12.34', $balance->balance()->toDecimalString());
    }
}
