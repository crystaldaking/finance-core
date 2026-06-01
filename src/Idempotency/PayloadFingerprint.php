<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use Crystal\Finance\Core\Exception\InvalidPayloadFingerprint;
use JsonException;

final readonly class PayloadFingerprint
{
    private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/^sha256:[a-f0-9]{64}$/', $value) !== 1) {
            throw InvalidPayloadFingerprint::fromString($value);
        }

        return new self($value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        try {
            $canonical = json_encode(self::canonicalize($payload), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidPayloadFingerprint::unsupportedPayload();
        }

        return new self('sha256:' . hash('sha256', $canonical));
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    /**
     * @return array<mixed>|bool|int|string|null
     * @psalm-suppress MixedAssignment The input payload is intentionally mixed until validated recursively.
     */
    private static function canonicalize(mixed $value): array|bool|int|string|null
    {
        if ($value === null || is_bool($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            throw InvalidPayloadFingerprint::unsupportedPayload();
        }

        if (array_is_list($value)) {
            return array_map(self::canonicalize(...), $value);
        }

        ksort($value);

        $canonical = [];

        foreach ($value as $key => $nestedValue) {
            if (!is_string($key)) {
                throw InvalidPayloadFingerprint::unsupportedPayload();
            }

            $canonical[$key] = self::canonicalize($nestedValue);
        }

        return $canonical;
    }
}
