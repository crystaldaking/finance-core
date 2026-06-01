<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

final readonly class IdempotencyResult
{
    private function __construct(
        private IdempotencyRecord $record,
        private bool $replayed,
    ) {
    }

    public static function fresh(IdempotencyRecord $record): self
    {
        return new self($record, false);
    }

    public static function replay(IdempotencyRecord $record): self
    {
        return new self($record, true);
    }

    public function record(): IdempotencyRecord
    {
        return $this->record;
    }

    public function replayed(): bool
    {
        return $this->replayed;
    }

    public function result(): mixed
    {
        return $this->record->result();
    }
}
