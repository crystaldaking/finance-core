<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidLedgerTransaction extends DomainException
{
    public static function empty(): self
    {
        return new self('Ledger transaction must contain at least one entry.');
    }

    public static function nonPositiveEntry(string $amount): self
    {
        return new self(sprintf('Ledger entries must use positive amounts, got "%s".', $amount));
    }

    public static function invalidIdentifier(string $identifier): self
    {
        return new self(sprintf('Invalid ledger identifier "%s".', $identifier));
    }

    public static function invalidReference(string $reference): self
    {
        return new self(sprintf('Invalid ledger reference "%s".', $reference));
    }

    public static function alreadyReversed(string $transactionId): self
    {
        return new self(sprintf('Ledger transaction "%s" has already been reversed.', $transactionId));
    }

    public static function reversalCannotBeReversed(string $transactionId): self
    {
        return new self(sprintf('Ledger reversal transaction "%s" cannot be reversed again.', $transactionId));
    }

    public static function duplicateReference(string $reference): self
    {
        return new self(sprintf('Ledger transaction reference "%s" already exists.', $reference));
    }
}
