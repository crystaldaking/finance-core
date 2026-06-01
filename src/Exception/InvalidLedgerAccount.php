<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidLedgerAccount extends DomainException
{
    public static function fromString(string $accountId): self
    {
        return new self(sprintf('Invalid ledger account id "%s". Use non-empty colon-separated lowercase segments.', $accountId));
    }
}
