<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Audit;

use Crystal\Finance\Core\Exception\InvalidAuditContext;

final readonly class RequestId
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{1,127}$/', $value) !== 1) {
            throw InvalidAuditContext::invalidIdentifier($value);
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
