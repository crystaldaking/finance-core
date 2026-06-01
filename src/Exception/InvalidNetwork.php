<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Exception;

final class InvalidNetwork extends DomainException
{
    public static function fromString(string $network): self
    {
        return new self(sprintf('Invalid network "%s". Use 2-32 uppercase A-Z, 0-9, underscore or hyphen characters.', $network));
    }
}
