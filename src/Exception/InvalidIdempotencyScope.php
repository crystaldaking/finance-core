<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidIdempotencyScope extends DomainException
{
    public static function fromString(string $scope): self
    {
        return new self(sprintf('Invalid idempotency scope "%s".', $scope));
    }
}
