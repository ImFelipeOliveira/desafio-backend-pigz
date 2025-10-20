<?php

declare(strict_types=1);

namespace App\Application\DTO\Vehicle;

final class ListVehicle
{
  public function __construct(
    private int $page = 1,
    private int $perPage = 15,
    private ?string $brand = null,
    private ?string $model = null,
    private ?int $year_min = null,
    private ?int $year_max = null,
    private ?float $minPrice = null,
    private ?float $maxPrice = null,
    private ?string $status = null
  ) {
    $this->page = max(1, $page);
    $this->perPage = min(100, max(1, $perPage));
  }

  public function getPage(): int
  {
    return $this->page;
  }

  public function getPerPage(): int
  {
    return $this->perPage;
  }

  public function getBrand(): ?string
  {
    return $this->brand;
  }

  public function getModel(): ?string
  {
    return $this->model;
  }

  public function getYearMin(): ?int
  {
    return $this->year_min;
  }

  public function getYearMax(): ?int
  {
    return $this->year_max;
  }

  public function getMinPrice(): ?float
  {
    return $this->minPrice;
  }

  public function getMaxPrice(): ?float
  {
    return $this->maxPrice;
  }

  public function getStatus(): ?string
  {
    return $this->status;
  }

  public function getFilters(): array
  {
    return array_filter([
      'brand' => $this->brand,
      'model' => $this->model,
      'year_min' => $this->year_min,
      'year_max' => $this->year_max,
      'minPrice' => $this->minPrice,
      'maxPrice' => $this->maxPrice,
      'status' => $this->status,
    ], fn($v) => $v !== null);
  }

  public function getOffset(): int
  {
    return ($this->page - 1) * $this->perPage;
  }
}
