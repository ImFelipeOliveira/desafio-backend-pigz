<?php

declare(strict_types=1);

namespace App\Application\DTO\Fipe;

class FipeEntryDTO
{
  public function __construct(
    private readonly string $fipeCode,
    private readonly string $brand,
    private readonly string $model,
    private readonly string $category,
    private readonly ?string $version,
    private readonly string $fuelType,
    private readonly float $priceValue,
    private readonly string $priceCurrency,
    private readonly string $referenceMonth, // YYYY-MM
    private readonly int $modelYear
  ) {}

  public function getFipeCode(): string
  {
    return $this->fipeCode;
  }

  public function getBrand(): string
  {
    return $this->brand;
  }

  public function getModel(): string
  {
    return $this->model;
  }

  public function getCategory(): string
  {
    return $this->category;
  }

  public function getVersion(): ?string
  {
    return $this->version;
  }

  public function getFuelType(): string
  {
    return $this->fuelType;
  }

  public function getPriceValue(): float
  {
    return $this->priceValue;
  }

  public function getPriceCurrency(): string
  {
    return $this->priceCurrency;
  }

  public function getReferenceMonth(): string
  {
    return $this->referenceMonth;
  }

  public function getModelYear(): int
  {
    return $this->modelYear;
  }
}
