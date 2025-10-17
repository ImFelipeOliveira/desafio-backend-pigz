<?php

declare(strict_types=1);

namespace App\Domain\Entity\ValueObjects;

class Mileage
{
    private int $kilometers;

    public function __construct(int $kilometers)
    {
        $this->validateKilometers($kilometers);
        $this->kilometers = $kilometers;
    }

    public function getKilometers(): int
    {
        return $this->kilometers;
    }

    public function getFormattedKilometers(): string
    {
        return number_format($this->kilometers, 0, ',', '.') . ' km';
    }

    public function isHighMileage(Year $year): bool
    {
        $vehicleAge = $year->getAge();
        $averageMileagePerYear = 20000; // 20.000 km por ano considerado normal

        if ($vehicleAge === 0) {
            return $this->kilometers > 5000; // Veículo 0km com mais de 5.000km é alta quilometragem
        }

        $expectedMileage = $vehicleAge * $averageMileagePerYear;

        return $this->kilometers > ($expectedMileage * 1.2); // 20% acima da média
    }

    public function isLowMileage(Year $year): bool
    {
        $vehicleAge = $year->getAge();
        $averageMileagePerYear = 20000;

        if ($vehicleAge === 0) {
            return true; // Veículo 0km sempre é baixa quilometragem
        }

        $expectedMileage = $vehicleAge * $averageMileagePerYear;

        return $this->kilometers < ($expectedMileage * 0.7); // 30% abaixo da média
    }

    public function getAveragePerYear(Year $year): float
    {
        $age = $year->getAge();

        if ($age === 0) {
            return (float) $this->kilometers; // Para veículos 0km
        }

        return $this->kilometers / $age;
    }

    public function add(Mileage $additional): Mileage
    {
        return new self($this->kilometers + $additional->kilometers);
    }

    public function equals(Mileage $other): bool
    {
        return $this->kilometers === $other->kilometers;
    }

    private function validateKilometers(int $kilometers): void
    {
        if ($kilometers < 0) {
            throw new \InvalidArgumentException('Mileage cannot be negative');
        }

        if ($kilometers > 2000000) { // 2 milhões de km é um limite razoável
            throw new \InvalidArgumentException('Mileage exceeds maximum allowed value');
        }
    }

    public function __toString(): string
    {
        return $this->getFormattedKilometers();
    }
}
