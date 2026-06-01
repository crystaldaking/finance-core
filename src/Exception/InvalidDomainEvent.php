<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidDomainEvent extends DomainException
{
    public static function invalidIdentifier(string $identifier): self
    {
        return new self(sprintf('Invalid domain event identifier "%s".', $identifier));
    }

    public static function invalidName(string $eventName): self
    {
        return new self(sprintf('Invalid domain event name "%s".', $eventName));
    }
}
