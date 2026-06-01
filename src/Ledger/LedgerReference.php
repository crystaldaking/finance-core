<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Ledger;

use Crystal\Finance\Core\Exception\InvalidLedgerTransaction;

final readonly class LedgerReference
{
    private function __construct(
        private string $type,
        private string $id,
    ) {
    }

    public static function of(string $type, string $id): self
    {
        if (preg_match('/^[a-z][a-z0-9_-]{1,63}$/', $type) !== 1) {
            throw InvalidLedgerTransaction::invalidReference($type . ':' . $id);
        }

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{1,127}$/', $id) !== 1) {
            throw InvalidLedgerTransaction::invalidReference($type . ':' . $id);
        }

        return new self($type, $id);
    }

    public static function manual(string $id): self
    {
        return self::of('manual', $id);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function value(): string
    {
        return $this->type . ':' . $this->id;
    }

    public function equals(self $other): bool
    {
        return $this->value() === $other->value();
    }

    #[\Override]
    public function __toString(): string
    {
        return $this->value();
    }
}
