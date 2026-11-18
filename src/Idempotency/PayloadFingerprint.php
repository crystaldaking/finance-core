<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Idempotency;

use Crystal\Finance\Core\Exception\InvalidPayloadFingerprint;
use JsonException;

final readonly class PayloadFingerprint
{
    private function __construct(
        private string $value,
        private ?string $legacyValue = null,
    ) {
    }

    public static function fromString(string $value): self
    {
        if (preg_match('/\Asha256:[a-f0-9]{64}\z/', $value) !== 1) {
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
            $canonical = json_encode(self::canonicalize($payload, SORT_STRING), JSON_THROW_ON_ERROR);
            $legacy = json_encode(self::canonicalize($payload, SORT_REGULAR), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw InvalidPayloadFingerprint::unsupportedPayload();
        }

        return new self(
            'sha256:' . hash('sha256', $canonical),
            $canonical === $legacy ? null : 'sha256:' . hash('sha256', $legacy),
        );
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
     * Match a requested payload against a stored fingerprint, including v1.1 fingerprints
     * calculated from the same input order.
     */
    public function matchesStored(self $stored): bool
    {
        return $this->equals($stored)
            || (isset($this->legacyValue) && hash_equals($stored->value, $this->legacyValue));
    }

    /**
     * @return array<mixed>|bool|int|string|null
     * @psalm-suppress MixedAssignment The input payload is intentionally mixed until validated recursively.
     */
    private static function canonicalize(mixed $value, int $keySort): array|bool|int|string|null
    {
        if ($value === null || is_bool($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            throw InvalidPayloadFingerprint::unsupportedPayload();
        }

        if (array_is_list($value)) {
            return array_map(
                static fn (mixed $item): array|bool|int|string|null => self::canonicalize($item, $keySort),
                $value,
            );
        }

        ksort($value, $keySort);

        $canonical = [];

        foreach ($value as $key => $nestedValue) {
            if (!is_string($key)) {
                throw InvalidPayloadFingerprint::unsupportedPayload();
            }

            $canonical[$key] = self::canonicalize($nestedValue, $keySort);
        }

        return $canonical;
    }
}
