<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class AssetMismatch extends DomainException
{
    public static function between(string $leftAssetId, string $rightAssetId): self
    {
        return new self(sprintf('Asset mismatch: "%s" cannot be combined with "%s".', $leftAssetId, $rightAssetId));
    }
}
