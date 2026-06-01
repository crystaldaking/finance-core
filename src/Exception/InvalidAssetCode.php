<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidAssetCode extends DomainException
{
    public static function fromString(string $code): self
    {
        return new self(sprintf('Invalid asset code "%s". Use 2-12 uppercase A-Z or 0-9 characters.', $code));
    }
}
