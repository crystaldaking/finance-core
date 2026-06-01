<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidAssetPair extends DomainException
{
    public static function sameBaseAndQuote(string $assetId): self
    {
        return new self(sprintf('Asset pair base and quote must differ, "%s" given for both.', $assetId));
    }
}
