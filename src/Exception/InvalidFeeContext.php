<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidFeeContext extends DomainException
{
    public static function invalidMetadataKey(int|string $key): self
    {
        return new self(sprintf('Invalid fee context metadata key "%s". Metadata keys must be strings.', (string) $key));
    }

    public static function invalidMetadataValue(string $key): self
    {
        return new self(sprintf('Invalid fee context metadata value for key "%s". Use string, int, bool or null.', $key));
    }
}
