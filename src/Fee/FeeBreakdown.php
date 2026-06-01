<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

final readonly class FeeBreakdown
{
    /**
     * @param list<FeeBreakdownLine> $lines
     */
    public function __construct(private array $lines)
    {
    }

    /**
     * @return list<FeeBreakdownLine>
     */
    public function lines(): array
    {
        return $this->lines;
    }
}
