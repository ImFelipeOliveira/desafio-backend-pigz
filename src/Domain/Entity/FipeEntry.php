<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\ValueObjects\FuelType;
use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\YearMonth;
use Symfony\Component\Uid\Uuid;

class FipeEntry
{
    private Uuid $id;
    private FipeCode $fipeCode;
    private VehicleSpecification $specification;
    private FuelType $fuelType;
    private Price $price;
    private YearMonth $referenceMonth;
    private int $modelYear;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(
        FipeCode $fipeCode,
        VehicleSpecification $specification,
        FuelType $fuelType,
        Price $price,
        YearMonth $referenceMonth,
        int $modelYear
    ) {
        $this->id = Uuid::v4();
        $this->fipeCode = $fipeCode;
        $this->specification = $specification;
        $this->fuelType = $fuelType;
        $this->price = $price;
        $this->referenceMonth = $referenceMonth;
        $this->modelYear = $modelYear;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = null;
    }

    // Getters
    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFipeCode(): FipeCode
    {
        return $this->fipeCode;
    }

    public function getSpecification(): VehicleSpecification
    {
        return $this->specification;
    }

    public function getFuelType(): FuelType
    {
        return $this->fuelType;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getReferenceMonth(): YearMonth
    {
        return $this->referenceMonth;
    }

    public function getModelYear(): int
    {
        return $this->modelYear;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // Business Logic Methods
    public function updatePrice(Price $newPrice): void
    {
        $this->price = $newPrice;
        $this->markAsUpdated();
    }

    public function isCurrent(): bool
    {
        return $this->referenceMonth->isCurrent();
    }

    public function isPast(): bool
    {
        return $this->referenceMonth->isPast();
    }

    public function isFuture(): bool
    {
        return $this->referenceMonth->isFuture();
    }

    public function calculateVariation(FipeEntry $previousEntry): ?float
    {
        if (!$this->fipeCode->equals($previousEntry->fipeCode)) {
            throw new \InvalidArgumentException('Cannot calculate variation between different FIPE codes');
        }

        if (!$this->fuelType === $previousEntry->fuelType) {
            throw new \InvalidArgumentException('Cannot calculate variation between different fuel types');
        }

        return $this->price->calculatePercentageDifference($previousEntry->price);
    }

    public function isMoreRecentThan(FipeEntry $other): bool
    {
        if ($this->referenceMonth->getYear() > $other->referenceMonth->getYear()) {
            return true;
        }

        if ($this->referenceMonth->getYear() === $other->referenceMonth->getYear()) {
            return $this->referenceMonth->getMonth() > $other->referenceMonth->getMonth();
        }

        return false;
    }

    public function getAgeInMonths(): int
    {
        $currentMonth = YearMonth::current();
        return $currentMonth->diffInMonths($this->referenceMonth);
    }

    public function matchesVehicle(Vehicle $vehicle): bool
    {
        // Verifica se esta entrada FIPE corresponde ao veículo
        if ($this->fipeCode !== null && $vehicle->getFipeCode() !== null) {
            return $this->fipeCode->equals($vehicle->getFipeCode());
        }

        // Fallback: compara especificação e combustível
        return $this->specification->equals($vehicle->getSpecification())
            && $this->fuelType === $vehicle->getFuelType()
            && $this->modelYear === $vehicle->getYear()->getValue();
    }

    private function markAsUpdated(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->toString(),
            'fipeCode' => $this->fipeCode->getCode(),
            'brand' => $this->specification->getBrand(),
            'model' => $this->specification->getModel(),
            'category' => $this->specification->getCategory(),
            'version' => $this->specification->getVersion(),
            'fuelType' => $this->fuelType->value,
            'price' => $this->price->getValue(),
            'currency' => $this->price->getCurrency(),
            'referenceMonth' => $this->referenceMonth->format(),
            'modelYear' => $this->modelYear,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %d (%s) - %s - %s',
            $this->specification->getFullName(),
            $this->modelYear,
            $this->fuelType->getLabel(),
            $this->price->getFormattedValue(),
            $this->referenceMonth->format('m/Y')
        );
    }
}
