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

    public static function reversedTransactionCannotBeModified(string $transactionId): self
    {
        return new self(sprintf('Ledger transaction "%s" has reversal state and cannot be modified.', $transactionId));
    }

    public static function reversalMustBeCreatedFromOriginal(): self
    {
        return new self('Ledger reversal transactions must be created by reversing an existing transaction.');
    }

    public static function reversalRequiresAtomicPersistence(): self
    {
        return new self('Ledger reversals must be persisted atomically with their marked original transaction.');
    }

    public static function invalidReversalState(string $transactionId): self
    {
        return new self(sprintf('Ledger transaction "%s" has inconsistent reversal type and state.', $transactionId));
    }

    public static function invalidReversalPair(): self
    {
        return new self('Ledger reversal does not match its original transaction.');
    }

    public static function invalidMetadataKey(int|string $key): self
    {
        return new self(sprintf('Invalid ledger metadata key "%s". Metadata keys must be strings.', (string) $key));
    }

    public static function invalidMetadataValue(string $key): self
    {
        return new self(sprintf('Invalid ledger metadata value for key "%s". Use string, int, bool or null.', $key));
    }
}
