<?php

declare(strict_types=1);

namespace App\Application\DTO\Vehicle;

final class VehicleDTO
{
  public function __construct(
    private string $brand,
    private string $model,
    private string $version,
    private string $category,
    private int $year,
    private float $priceValue,
    private string $priceCurrency,
    private int $mileageValue,
    private string $mileageUnit,
    private string $fuelType,
    private string $transmission,
    private string $fipeCode,
    private ?string $vin = null,
    private ?string $description = null,
    private array $images = []
  ) {}

  public function getBrand(): string
  {
    return $this->brand;
  }

  public function getModel(): string
  {
    return $this->model;
  }

  public function getVersion(): string
  {
    return $this->version;
  }

  public function getCategory(): string
  {
    return $this->category;
  }

  public function getYear(): int
  {
    return $this->year;
  }

  public function getPriceValue(): float
  {
    return $this->priceValue;
  }

  public function getPriceCurrency(): string
  {
    return $this->priceCurrency;
  }

  public function getMileageValue(): int
  {
    return $this->mileageValue;
  }

  public function getMileageUnit(): string
  {
    return $this->mileageUnit;
  }

  public function getFuelType(): string
  {
    return $this->fuelType;
  }

  public function getTransmission(): string
  {
    return $this->transmission;
  }

  public function getFipeCode(): string
  {
    return $this->fipeCode;
  }

  public function getVin(): ?string
  {
    return $this->vin;
  }

  public function getDescription(): ?string
  {
    return $this->description;
  }

  public function getImages(): array
  {
    return $this->images;
  }
}
