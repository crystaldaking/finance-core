<?php

declare(strict_types=1);

namespace Crystal\Finance\Core\Fee;

use Crystal\Finance\Core\Money\Money;
use Crystal\Finance\Core\Money\Percentage;
use Crystal\Finance\Core\Money\RoundingMode;

final readonly class FeeRule
{
    /**
     * @param list<FeeComponent> $components
     */
    private function __construct(
        private array $components,
        private bool $negativeNetAllowed,
    ) {
    }

    public static function make(): self
    {
        return new self([], false);
    }

    #[\NoDiscard]
    public function percent(
        Percentage $percentage,
        string $label = 'percentage_fee',
        RoundingMode $roundingMode = RoundingMode::HalfUp,
    ): self {
        return $this->withComponent(FeeComponent::percentage($percentage, $label, $roundingMode));
    }

    #[\NoDiscard]
    public function fixed(Money $amount, string $label = 'fixed_fee'): self
    {
        return $this->withComponent(FeeComponent::fixed($amount, $label));
    }

    #[\NoDiscard]
    public function min(Money $amount, string $label = 'minimum_fee'): self
    {
        return $this->withComponent(FeeComponent::minimum($amount, $label));
    }

    #[\NoDiscard]
    public function max(Money $amount, string $label = 'maximum_fee'): self
    {
        return $this->withComponent(FeeComponent::maximum($amount, $label));
    }

    #[\NoDiscard]
    public function allowNegativeNet(): self
    {
        return new self($this->components, true);
    }

    /**
     * @return list<FeeComponent>
     */
    public function components(): array
    {
        return $this->components;
    }

    public function negativeNetAllowed(): bool
    {
        return $this->negativeNetAllowed;
    }

    private function withComponent(FeeComponent $component): self
    {
        $components = $this->components;
        $components[] = $component;

        return new self($components, $this->negativeNetAllowed);
    }
}
