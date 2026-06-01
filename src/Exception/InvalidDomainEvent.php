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

    public static function invalidAggregateId(string $aggregateId): self
    {
        return new self(sprintf('Invalid domain event aggregate id "%s".', $aggregateId));
    }

    public static function invalidMetadataKey(int|string $key): self
    {
        return new self(sprintf('Invalid domain event metadata key "%s". Metadata keys must be strings.', (string) $key));
    }

    public static function invalidMetadataValue(string $key): self
    {
        return new self(sprintf('Invalid domain event metadata value for key "%s". Use string, int, bool or null.', $key));
    }
}
