<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entity\Vehicle;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\VehicleStatus;

interface VehicleRepositoryInterface
{
  public function save(Vehicle $vehicle): void;

  public function getAll(int $page, int $limit, array $filters = []): array;

  public function update(Vehicle $vehicle): void;

  public function findById(string $id): ?Vehicle;

  public function findByOwnerId(string $ownerId): array;

  public function findByStatus(VehicleStatus $status): array;

  public function findBySpecification(VehicleSpecification $specification): array;

  public function findByFipeCode(FipeCode $fipeCode): array;

  public function findByPriceRange(Price $minPrice, Price $maxPrice): array;

  public function findSimilarVehicles(Vehicle $vehicle, int $limit = 10): array;

  public function findAvailableVehicles(int $limit = 20, int $offset = 0): array;

  public function delete(Vehicle $vehicle): void;

  public function exists(string $id): bool;

  public function countByOwnerId(string $ownerId): int;

  public function countByStatus(VehicleStatus $status): int;

  /**
   * Find vehicles by filters with pagination
   * @param array $filters Filtros como brand, model, price_min, price_max, year_min, year_max, fuelType
   * @param int $page Página atual
   * @param int $limit Itens por página
   * @return Vehicle[]
   */
  public function findByFilters(array $filters, int $page, int $limit): array;

  /**
   * Count vehicles by filters
   * @param array $filters Filtros como brand, model, price_min, price_max, year_min, year_max, fuelType
   * @return int
   */
  public function countByFilters(array $filters): int;
}
