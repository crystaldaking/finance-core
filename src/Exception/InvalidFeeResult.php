<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidFeeResult extends DomainException
{
    public static function invalidBreakdownLines(): self
    {
        return new self('Fee breakdown lines must be a list of FeeBreakdownLine instances.');
    }

    public static function netMismatch(string $expectedNet, string $actualNet): self
    {
        return new self(sprintf(
            'Fee result net mismatch: expected "%s", got "%s".',
            $expectedNet,
            $actualNet,
        ));
    }

    public static function breakdownAssetMismatch(string $expectedAssetId, string $actualAssetId): self
    {
        return new self(sprintf(
            'Fee breakdown asset mismatch: expected "%s", got "%s".',
            $expectedAssetId,
            $actualAssetId,
        ));
    }

    public static function breakdownTotalMismatch(string $expectedTotal, string $actualTotal): self
    {
        return new self(sprintf(
            'Fee breakdown total mismatch: expected "%s", got "%s".',
            $expectedTotal,
            $actualTotal,
        ));
    }
}
