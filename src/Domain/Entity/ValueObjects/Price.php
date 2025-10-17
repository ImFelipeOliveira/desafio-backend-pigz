<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

class Price
{
    private float $value;
    private string $currency;

    public function __construct(float $value, string $currency = 'BRL')
    {
        $this->validateValue($value);
        $this->validateCurrency($currency);

        $this->value = $value;
        $this->currency = strtoupper($currency);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getFormattedValue(): string
    {
        return $this->currency . ' ' . number_format($this->value, 2, ',', '.');
    }

    public function add(Price $other): Price
    {
        $this->ensureSameCurrency($other);
        return new self($this->value + $other->value, $this->currency);
    }

    public function subtract(Price $other): Price
    {
        $this->ensureSameCurrency($other);
        return new self($this->value - $other->value, $this->currency);
    }

    public function multiply(float $multiplier): Price
    {
        return new self($this->value * $multiplier, $this->currency);
    }

    public function calculatePercentageDifference(Price $other): float
    {
        $this->ensureSameCurrency($other);

        if ($other->value == 0) {
            return 0;
        }

        return (($this->value - $other->value) / $other->value) * 100;
    }

    public function isGreaterThan(Price $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->value > $other->value;
    }

    public function isLessThan(Price $other): bool
    {
        $this->ensureSameCurrency($other);
        return $this->value < $other->value;
    }

    public function equals(Price $other): bool
    {
        return $this->currency === $other->currency
            && abs($this->value - $other->value) < 0.01;
    }

    private function validateValue(float $value): void
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }

        if ($value > 999999999.99) {
            throw new \InvalidArgumentException('Price exceeds maximum allowed value');
        }
    }

    private function validateCurrency(string $currency): void
    {
        $allowedCurrencies = ['BRL', 'USD', 'EUR'];

        if (!in_array(strtoupper($currency), $allowedCurrencies)) {
            throw new \InvalidArgumentException('Invalid currency');
        }
    }

    private function ensureSameCurrency(Price $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot operate with different currencies');
        }
    }

    public function __toString(): string
    {
        return $this->getFormattedValue();
    }
}
