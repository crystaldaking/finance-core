<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidAsset extends DomainException
{
    public static function invalidScale(int $scale): self
    {
        return new self(sprintf('Invalid asset scale "%d". Scale must be between 0 and 36.', $scale));
    }

    public static function missingNetwork(string $code): self
    {
        return new self(sprintf('Crypto asset "%s" must include an explicit network.', $code));
    }

    public static function notFound(string $assetId): self
    {
        return new self(sprintf('Asset "%s" is not registered.', $assetId));
    }

    public static function duplicate(string $assetId): self
    {
        return new self(sprintf('Asset "%s" is already registered.', $assetId));
    }
}
