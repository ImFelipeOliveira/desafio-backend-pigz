<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\Year;
use App\Domain\Entity\ValueObjects\VehicleStatus;
use App\Domain\Entity\ValueObjects\Mileage;
use App\Domain\Entity\ValueObjects\VIN;
use App\Domain\Entity\ValueObjects\FuelType;
use App\Domain\Entity\ValueObjects\TransmissionType;
use App\Domain\Entity\ValueObjects\FipeCode;

class Vehicle
{
  private string $id;
  private VehicleSpecification $specification;
  private Year $year;
  private Price $price;
  private VehicleStatus $status;
  private Mileage $mileage;
  private ?VIN $vin;
  private FuelType $fuelType;
  private TransmissionType $transmission;
  private FipeCode $fipeCode;
  private ?string $description;
  private array $images;
  private string $ownerId; // Referência ao User
  private \DateTimeImmutable $createdAt;
  private \DateTimeImmutable $updatedAt;

  public function __construct(
    string $id,
    VehicleSpecification $specification,
    Year $year,
    Price $price,
    Mileage $mileage,
    FuelType $fuelType,
    TransmissionType $transmission,
    FipeCode $fipeCode,
    string $ownerId,
    ?VIN $vin = null,
    ?string $description = null
  ) {
    $this->id = $id;
    $this->specification = $specification;
    $this->year = $year;
    $this->price = $price;
    $this->status = VehicleStatus::AVAILABLE;
    $this->mileage = $mileage;
    $this->vin = $vin;
    $this->fuelType = $fuelType;
    $this->transmission = $transmission;
    $this->fipeCode = $fipeCode;
    $this->description = $description;
    $this->images = [];
    $this->ownerId = $ownerId;
    $this->createdAt = new \DateTimeImmutable();
    $this->updatedAt = new \DateTimeImmutable();
  }

  // Getters
  public function getId(): string
  {
    return $this->id;
  }

  public function getSpecification(): VehicleSpecification
  {
    return $this->specification;
  }

  public function getYear(): Year
  {
    return $this->year;
  }

  public function getPrice(): Price
  {
    return $this->price;
  }

  public function getStatus(): VehicleStatus
  {
    return $this->status;
  }

  public function getMileage(): Mileage
  {
    return $this->mileage;
  }

  public function getVIN(): ?VIN
  {
    return $this->vin;
  }

  public function getFuelType(): FuelType
  {
    return $this->fuelType;
  }

  public function getTransmission(): TransmissionType
  {
    return $this->transmission;
  }

  public function getFipeCode(): FipeCode
  {
    return $this->fipeCode;
  }

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function getImages(): array
  {
    return $this->images;
  }

  public function getOwnerId(): string
  {
    return $this->ownerId;
  }

  public function getCreatedAt(): \DateTimeImmutable
  {
    return $this->createdAt;
  }

  public function getUpdatedAt(): \DateTimeImmutable
  {
    return $this->updatedAt;
  }

  // Business Methods
  public function updatePrice(Price $newPrice): void
  {
    if (!$this->canUpdatePrice()) {
      throw new \DomainException('Cannot update price of vehicle in current status');
    }

    $this->price = $newPrice;
    $this->updateTimestamp();
  }

  public function reserve(): void
  {
    if (!$this->status->canBeReserved()) {
      throw new \DomainException('Vehicle cannot be reserved in current status');
    }

    $this->status = VehicleStatus::RESERVED;
    $this->updateTimestamp();
  }

  public function markAsSold(): void
  {
    if (!$this->status->canBeSold()) {
      throw new \DomainException('Vehicle cannot be marked as sold in current status');
    }

    $this->status = VehicleStatus::SOLD;
    $this->updateTimestamp();
  }

  public function makeAvailable(): void
  {
    if ($this->status === VehicleStatus::SOLD) {
      throw new \DomainException('Sold vehicle cannot be made available');
    }

    $this->status = VehicleStatus::AVAILABLE;
    $this->updateTimestamp();
  }

  public function sendToMaintenance(): void
  {
    $this->status = VehicleStatus::MAINTENANCE;
    $this->updateTimestamp();
  }

  public function deactivate(): void
  {
    $this->status = VehicleStatus::INACTIVE;
    $this->updateTimestamp();
  }

  public function updateMileage(Mileage $newMileage): void
  {
    // Quilometragem só pode aumentar
    if ($newMileage->getKilometers() < $this->mileage->getKilometers()) {
      throw new \DomainException('Mileage cannot be decreased');
    }

    $this->mileage = $newMileage;
    $this->updateTimestamp();
  }

  public function addImage(string $imageUrl): void
  {
    if (count($this->images) >= 20) { // Limite de 20 imagens
      throw new \DomainException('Maximum number of images reached');
    }

    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
      throw new \InvalidArgumentException('Invalid image URL');
    }

    $this->images[] = $imageUrl;
    $this->updateTimestamp();
  }

  public function removeImage(string $imageUrl): void
  {
    $key = array_search($imageUrl, $this->images, true);
    if ($key !== false) {
      unset($this->images[$key]);
      $this->images = array_values($this->images); // Reindex array
      $this->updateTimestamp();
    }
  }

  public function updateDescription(string $description): void
  {
    $this->description = $description;
    $this->updateTimestamp();
  }

  public function isAvailableForSale(): bool
  {
    return $this->status === VehicleStatus::AVAILABLE;
  }

  public function isReserved(): bool
  {
    return $this->status === VehicleStatus::RESERVED;
  }

  public function isSold(): bool
  {
    return $this->status === VehicleStatus::SOLD;
  }

  public function getAge(): int
  {
    return $this->year->getAge();
  }

  public function isClassicCar(): bool
  {
    return $this->year->isClassic();
  }

  public function hasHighMileage(): bool
  {
    return $this->mileage->isHighMileage($this->year);
  }

  public function getFullName(): string
  {
    return sprintf(
      '%s %s %s %d',
      $this->specification->getBrand(),
      $this->specification->getModel(),
      $this->specification->getVersion(),
      $this->year->getValue()
    );
  }

  public function getMainCharacteristics(): array
  {
    return [
      'fuel' => $this->fuelType->getLabel(),
      'transmission' => $this->transmission->getShortLabel(),
      'year' => $this->year->getValue(),
      'mileage' => $this->mileage->getFormattedKilometers(),
      'category' => $this->specification->getCategory()
    ];
  }

  public function canBeComparedWith(Vehicle $other): bool
  {
    // Veículos podem ser comparados se forem da mesma categoria
    return $this->specification->getCategory() === $other->specification->getCategory();
  }

  private function canUpdatePrice(): bool
  {
    return in_array($this->status, [
      VehicleStatus::AVAILABLE,
      VehicleStatus::RESERVED
    ], true);
  }

  private function updateTimestamp(): void
  {
    $this->updatedAt = new \DateTimeImmutable();
  }
}
