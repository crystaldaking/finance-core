<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class UnbalancedLedgerTransaction extends DomainException
{
    public static function forAsset(string $assetId, string $debits, string $credits): self
    {
        return new self(sprintf(
            'Ledger transaction is unbalanced for asset "%s": debits "%s" do not equal credits "%s".',
            $assetId,
            $debits,
            $credits,
        ));
    }
}
