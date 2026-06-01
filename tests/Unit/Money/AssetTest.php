<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Tests\Unit\Money;

use Crystal\Finance\Core\Exception\InvalidAsset;
use Crystal\Finance\Core\Exception\InvalidAssetCode;
use Crystal\Finance\Core\Exception\InvalidNetwork;
use Crystal\Finance\Core\Money\Asset;
use Crystal\Finance\Core\Money\AssetId;
use Crystal\Finance\Core\Money\AssetRegistry;
use Crystal\Finance\Core\Money\AssetType;
use PHPUnit\Framework\TestCase;

final class AssetTest extends TestCase
{
    public function testFiatAssetUsesCodeAsId(): void
    {
        $asset = Asset::fiat('EUR', 2);

        self::assertSame('EUR', $asset->id()->value());
        self::assertSame('EUR', $asset->code()->value());
        self::assertNull($asset->network());
        self::assertSame(AssetType::Fiat, $asset->type());
        self::assertSame(2, $asset->scale());
    }

    public function testCryptoAssetUsesNetworkAwareId(): void
    {
        $tronUsdt = Asset::crypto('USDT', 'TRON', 6);
        $ethereumUsdt = Asset::crypto('USDT', 'ETHEREUM', 6);

        self::assertSame('USDT@TRON', $tronUsdt->id()->value());
        self::assertSame('USDT@ETHEREUM', $ethereumUsdt->id()->value());
        self::assertFalse($tronUsdt->equals($ethereumUsdt));
    }

    public function testDefaultRegistryContainsNetworkAwareCryptoAssets(): void
    {
        $registry = AssetRegistry::default();

        self::assertTrue($registry->has('EUR'));
        self::assertTrue($registry->has('BTC@BITCOIN'));
        self::assertTrue($registry->has('ETH@ETHEREUM'));
        self::assertTrue($registry->has('USDT@TRON'));
        self::assertTrue($registry->has('USDT@ETHEREUM'));
        self::assertNotSame(
            $registry->get('USDT@TRON')->id()->value(),
            $registry->get('USDT@ETHEREUM')->id()->value(),
        );
    }

    public function testInvalidAssetCodeIsRejected(): void
    {
        $this->expectException(InvalidAssetCode::class);

        Asset::fiat('eur', 2);
    }

    public function testInvalidNetworkIsRejected(): void
    {
        $this->expectException(InvalidNetwork::class);

        Asset::crypto('USDT', 'tron mainnet', 6);
    }

    public function testInvalidScaleIsRejected(): void
    {
        $this->expectException(InvalidAsset::class);

        Asset::fiat('EUR', 37);
    }

    public function testAssetIdParsesCryptoFormat(): void
    {
        $id = AssetId::fromString('USDT@TRON');

        self::assertSame('USDT@TRON', $id->value());
    }
}
