<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidAuditContext extends DomainException
{
    public static function invalidIdentifier(string $identifier): self
    {
        return new self(sprintf('Invalid audit identifier "%s".', $identifier));
    }

    public static function emptyReason(): self
    {
        return new self('Audit reason cannot be empty.');
    }
}
