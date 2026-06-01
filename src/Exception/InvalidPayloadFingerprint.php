<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidPayloadFingerprint extends DomainException
{
    public static function fromString(string $fingerprint): self
    {
        return new self(sprintf('Invalid payload fingerprint "%s".', $fingerprint));
    }

    public static function unsupportedPayload(): self
    {
        return new self('Payload fingerprint can only be created from JSON-compatible arrays.');
    }
}
