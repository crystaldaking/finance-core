<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidPercentage extends DomainException
{
    public static function fromString(string $percentage): self
    {
        return new self(sprintf('Invalid percentage "%s". Use a plain decimal string.', $percentage));
    }
}
