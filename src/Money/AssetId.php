<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Money;

use Crystal\Finance\Core\Exception\InvalidAsset;
use Crystal\Finance\Core\Exception\InvalidAssetCode;
use Crystal\Finance\Core\Exception\InvalidNetwork;

final readonly class AssetId
{
    private function __construct(
        private AssetCode $code,
        private ?Network $network,
    ) {
    }

    public static function fiat(AssetCode|string $code): self
    {
        return new self(self::parseCode($code), null);
    }

    public static function crypto(AssetCode|string $code, Network|string $network): self
    {
        return new self(self::parseCode($code), self::parseNetwork($network));
    }

    public static function fromString(string $value): self
    {
        $parts = explode('@', $value);

        if (count($parts) === 1) {
            return self::fiat($parts[0]);
        }

        if (count($parts) === 2) {
            return self::crypto($parts[0], $parts[1]);
        }

        throw InvalidAsset::malformedId($value);
    }

    public function code(): AssetCode
    {
        return $this->code;
    }

    public function network(): ?Network
    {
        return $this->network;
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }

    public function value(): string
    {
        if ($this->network === null) {
            return $this->code->value();
        }

        return $this->code->value() . '@' . $this->network->value();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value();
    }

    private static function parseCode(AssetCode|string $code): AssetCode
    {
        if ($code instanceof AssetCode) {
            return $code;
        }

        try {
            return AssetCode::fromString($code);
        } catch (InvalidAssetCode $exception) {
            throw $exception;
        }
    }

    private static function parseNetwork(Network|string $network): Network
    {
        if ($network instanceof Network) {
            return $network;
        }

        try {
            return Network::fromString($network);
        } catch (InvalidNetwork $exception) {
            throw $exception;
        }
    }
}
