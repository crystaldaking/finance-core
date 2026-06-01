<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidExchangeConversion extends DomainException
{
    public static function sourceAssetMismatch(string $actualAssetId, string $expectedBaseAssetId): self
    {
        return new self(sprintf(
            'Exchange conversion source asset mismatch: expected "%s", got "%s".',
            $expectedBaseAssetId,
            $actualAssetId,
        ));
    }
}
