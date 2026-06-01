<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Audit;

use Crystal\Finance\Core\Exception\InvalidAuditContext;

final readonly class Actor
{
    private function __construct(
        private ActorType $type,
        private string $id,
    ) {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._:@-]{1,127}$/', $id) !== 1) {
            throw InvalidAuditContext::invalidIdentifier($id);
        }
    }

    public static function system(string $id = 'system'): self
    {
        return new self(ActorType::System, $id);
    }

    public static function of(ActorType $type, string $id): self
    {
        return new self($type, $id);
    }

    public function type(): ActorType
    {
        return $this->type;
    }

    public function id(): string
    {
        return $this->id;
    }
}
