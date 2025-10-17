<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entity\VehicleComparison;
use App\Domain\Entity\Vehicle;

interface VehicleComparisonRepositoryInterface
{
  public function save(VehicleComparison $comparison): void;

  public function findById(string $id): ?VehicleComparison;

  public function findByUserId(string $userId): array;

  public function findByTargetVehicle(Vehicle $vehicle): array;

  public function findRecentByUserId(string $userId, int $limit = 10): array;

  public function delete(VehicleComparison $comparison): void;

  public function exists(string $id): bool;

  public function countByUserId(string $userId): int;

  /**
   * Busca comparações que precisam ser atualizadas
   */
  public function findOutdatedComparisons(int $limit = 100): array;
}
