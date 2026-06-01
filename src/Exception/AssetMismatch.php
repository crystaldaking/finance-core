<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class AssetMismatch extends DomainException
{
    public static function between(string $leftAssetId, string $rightAssetId): self
    {
        return new self(sprintf('Asset mismatch: "%s" cannot be combined with "%s".', $leftAssetId, $rightAssetId));
    }

    public static function incompatibleConfiguration(
        string $assetId,
        string $leftType,
        int $leftScale,
        string $rightType,
        int $rightScale,
    ): self {
        return new self(sprintf(
            'Asset "%s" has incompatible configuration: %s scale %d cannot be combined with %s scale %d.',
            $assetId,
            $leftType,
            $leftScale,
            $rightType,
            $rightScale,
        ));
    }
}
