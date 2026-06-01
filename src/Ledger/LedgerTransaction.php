<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Money\Money;

final readonly class LedgerTransaction
{
    /**
     * @param list<LedgerEntry> $entries
     */
    private function __construct(
        private LedgerTransactionId $id,
        private LedgerTransactionType $type,
        private LedgerReference $reference,
        private LedgerMetadata $metadata,
        private array $entries,
        private ?LedgerTransactionId $reversalOf,
        private ?LedgerTransactionId $reversedBy,
    ) {
    }

    public static function make(
        LedgerTransactionType $type,
        LedgerReference $reference,
        ?LedgerMetadata $metadata = null,
        ?LedgerTransactionId $id = null,
    ): self {
        return new self(
            $id ?? LedgerTransactionId::generate(),
            $type,
            $reference,
            $metadata ?? LedgerMetadata::empty(),
            [],
            null,
            null,
        );
    }

    public function debit(LedgerAccountId $accountId, Money $amount, ?LedgerEntryId $entryId = null): self
    {
        return $this->withEntry(LedgerEntry::debit($accountId, $amount, $entryId));
    }

    public function credit(LedgerAccountId $accountId, Money $amount, ?LedgerEntryId $entryId = null): self
    {
        return $this->withEntry(LedgerEntry::credit($accountId, $amount, $entryId));
    }

    public function withMetadata(LedgerMetadata $metadata): self
    {
        return new self(
            $this->id,
            $this->type,
            $this->reference,
            $metadata,
            $this->entries,
            $this->reversalOf,
            $this->reversedBy,
        );
    }

    public function reverse(LedgerReference $reference, ?LedgerTransactionId $id = null): self
    {
        if ($this->reversalOf !== null) {
            throw \Crystal\Finance\Core\Exception\InvalidLedgerTransaction::reversalCannotBeReversed($this->id->value());
        }

        if ($this->reversedBy !== null) {
            throw \Crystal\Finance\Core\Exception\InvalidLedgerTransaction::alreadyReversed($this->id->value());
        }

        $entries = [];

        foreach ($this->entries as $entry) {
            $entries[] = $entry->reversed();
        }

        return new self(
            $id ?? LedgerTransactionId::generate(),
            LedgerTransactionType::Reversal,
            $reference,
            $this->metadata->with('reversal_of', $this->id->value()),
            $entries,
            $this->id,
            null,
        );
    }

    public function markReversedBy(LedgerTransactionId $reversalId): self
    {
        if ($this->reversedBy !== null) {
            throw \Crystal\Finance\Core\Exception\InvalidLedgerTransaction::alreadyReversed($this->id->value());
        }

        return new self(
            $this->id,
            $this->type,
            $this->reference,
            $this->metadata->with('reversed_by', $reversalId->value()),
            $this->entries,
            $this->reversalOf,
            $reversalId,
        );
    }

    public function id(): LedgerTransactionId
    {
        return $this->id;
    }

    public function type(): LedgerTransactionType
    {
        return $this->type;
    }

    public function reference(): LedgerReference
    {
        return $this->reference;
    }

    public function metadata(): LedgerMetadata
    {
        return $this->metadata;
    }

    /**
     * @return list<LedgerEntry>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    public function reversalOf(): ?LedgerTransactionId
    {
        return $this->reversalOf;
    }

    public function reversedBy(): ?LedgerTransactionId
    {
        return $this->reversedBy;
    }

    public function isReversal(): bool
    {
        return $this->reversalOf !== null;
    }

    private function withEntry(LedgerEntry $entry): self
    {
        $entries = $this->entries;
        $entries[] = $entry;

        return new self(
            $this->id,
            $this->type,
            $this->reference,
            $this->metadata,
            $entries,
            $this->reversalOf,
            $this->reversedBy,
        );
    }
}
