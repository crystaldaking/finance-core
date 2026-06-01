<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;

final readonly class LedgerEntryId
{
    private function __construct(private string $value)
    {
    }

    public static function generate(): self
    {
        return new self('le_' . bin2hex(random_bytes(16)));
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{1,127}$/', $value) !== 1) {
            throw InvalidLedgerTransaction::invalidIdentifier($value);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value;
    }
}
