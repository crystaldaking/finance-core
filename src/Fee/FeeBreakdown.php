<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

use Crystal\Finance\Core\Exception\InvalidFeeResult;

final readonly class FeeBreakdown
{
    /**
     * @var list<FeeBreakdownLine>
     */
    private array $lines;

    /**
     * @param list<FeeBreakdownLine> $lines
     */
    public function __construct(array $lines)
    {
        $this->lines = self::validLines($lines);
    }

    /**
     * @param array<array-key, mixed> $lines
     * @return list<FeeBreakdownLine>
     */
    private static function validLines(array $lines): array
    {
        if (!array_is_list($lines)) {
            throw InvalidFeeResult::invalidBreakdownLines();
        }

        $validLines = [];

        foreach ($lines as $line) {
            if (!$line instanceof FeeBreakdownLine) {
                throw InvalidFeeResult::invalidBreakdownLines();
            }

            $validLines[] = $line;
        }

        return $validLines;
    }

    public static function of(FeeBreakdownLine ...$lines): self
    {
        return new self(array_values($lines));
    }

    /**
     * @return list<FeeBreakdownLine>
     */
    public function lines(): array
    {
        return $this->lines;
    }
}
