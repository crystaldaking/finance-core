<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Crystal\Finance\Core\Exception\InvalidAsset;

final readonly class Asset
{
    private AssetId $id;

    private AssetType $type;

    /**
     * @var int<0, 36>
     */
    private int $scale;

    private bool $active;

    private function __construct(AssetId $id, AssetType $type, int $scale, bool $active)
    {
        if ($type === AssetType::Crypto && $id->network() === null) {
            throw InvalidAsset::missingNetwork($id->code()->value());
        }

        $this->id = $id;
        $this->type = $type;
        $this->scale = self::validScale($scale);
        $this->active = $active;
    }

    public static function fiat(AssetCode|string $code, int $minorUnits = 2, bool $active = true): self
    {
        return new self(AssetId::fiat($code), AssetType::Fiat, $minorUnits, $active);
    }

    public static function crypto(AssetCode|string $code, Network|string $network, int $decimals, bool $active = true): self
    {
        return new self(AssetId::crypto($code, $network), AssetType::Crypto, $decimals, $active);
    }

    public function id(): AssetId
    {
        return $this->id;
    }

    public function code(): AssetCode
    {
        return $this->id->code();
    }

    public function network(): ?Network
    {
        return $this->id->network();
    }

    public function type(): AssetType
    {
        return $this->type;
    }

    /**
     * @return int<0, 36>
     */
    public function scale(): int
    {
        return $this->scale;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function equals(self $other): bool
    {
        return $this->id->equals($other->id);
    }

    /**
     * @return int<0, 36>
     */
    private static function validScale(int $scale): int
    {
        if ($scale < 0 || $scale > 36) {
            throw InvalidAsset::invalidScale($scale);
        }

        return $scale;
    }
}
