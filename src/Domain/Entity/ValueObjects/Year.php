<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

class Year
{
    private int $value;

    public function __construct(int $year)
    {
        $this->validateYear($year);
        $this->value = $year;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getAge(): int
    {
        $currentYear = (int) date('Y');
        return $currentYear - $this->value;
    }

    public function isClassic(): bool
    {
        return $this->getAge() >= 30;
    }

    public function isSemiNew(): bool
    {
        return $this->getAge() <= 3;
    }

    public function isUsed(): bool
    {
        return $this->getAge() > 0;
    }

    public function equals(Year $other): bool
    {
        return $this->value === $other->value;
    }

    private function validateYear(int $year): void
    {
        $currentYear = (int) date('Y');
        $minimumYear = 1900;
        $maximumYear = $currentYear + 1; // Permite próximo ano

        if ($year < $minimumYear) {
            throw new \InvalidArgumentException("Year cannot be before {$minimumYear}");
        }

        if ($year > $maximumYear) {
            throw new \InvalidArgumentException("Year cannot be after {$maximumYear}");
        }
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
