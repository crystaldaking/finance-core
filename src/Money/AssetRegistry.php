<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Crystal\Finance\Core\Exception\InvalidAsset;

final readonly class AssetRegistry
{
    /**
     * @param array<string, Asset> $assets
     */
    private function __construct(private array $assets)
    {
    }

    public static function default(): self
    {
        return self::of(
            Asset::fiat('EUR', 2),
            Asset::fiat('USD', 2),
            Asset::fiat('GBP', 2),
            Asset::fiat('RSD', 2),
            Asset::crypto('BTC', 'BITCOIN', 8),
            Asset::crypto('ETH', 'ETHEREUM', 18),
            Asset::crypto('USDT', 'TRON', 6),
            Asset::crypto('USDT', 'ETHEREUM', 6),
        );
    }

    public static function of(Asset ...$assets): self
    {
        $indexed = [];

        foreach ($assets as $asset) {
            $assetId = $asset->id()->value();

            if (isset($indexed[$assetId])) {
                throw InvalidAsset::duplicate($assetId);
            }

            $indexed[$assetId] = $asset;
        }

        return new self($indexed);
    }

    public function get(AssetId|string $assetId): Asset
    {
        $id = $assetId instanceof AssetId ? $assetId : AssetId::fromString($assetId);
        $key = $id->value();

        return $this->assets[$key] ?? throw InvalidAsset::notFound($key);
    }

    public function has(AssetId|string $assetId): bool
    {
        $id = $assetId instanceof AssetId ? $assetId : AssetId::fromString($assetId);

        return isset($this->assets[$id->value()]);
    }

    /**
     * @return array<string, Asset>
     */
    public function all(): array
    {
        return $this->assets;
    }
}
