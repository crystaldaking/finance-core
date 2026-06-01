<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerAccount;

final readonly class LedgerAccountId
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[a-z0-9][a-z0-9_-]*(?::[a-z0-9][a-z0-9_-]*)*$/', $value) !== 1) {
            throw InvalidLedgerAccount::fromString($value);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value;
    }
}
