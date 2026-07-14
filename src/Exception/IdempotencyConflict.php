<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class IdempotencyConflict extends DomainException
{
    public static function fingerprintMismatch(string $scope, string $key): self
    {
        return new self(sprintf('Idempotency key "%s" in scope "%s" was already used with a different payload fingerprint.', $key, $scope));
    }

    public static function alreadyStarted(string $scope, string $key): self
    {
        return new self(sprintf('Idempotency key "%s" in scope "%s" is already started.', $key, $scope));
    }

    public static function staleClaim(string $scope, string $key): self
    {
        return new self(sprintf('Idempotency claim for key "%s" in scope "%s" is no longer active.', $key, $scope));
    }
}
