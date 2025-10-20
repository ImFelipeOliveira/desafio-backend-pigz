<?php

declare(strict_types=1);

namespace App\Domain\Repositories;

use App\Domain\Entity\FipeEntry;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\ValueObjects\YearMonth;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\FuelType;

interface FipeEntryRepositoryInterface
{
  public function save(FipeEntry $fipeEntry): void;

  public function findById(string $id): ?FipeEntry;

  public function findByFipeCode(FipeCode $fipeCode): array;

  public function findByReferenceMonth(YearMonth $month): array;

  public function findBySpecification(VehicleSpecification $specification): array;

  public function findLatest(FipeCode $fipeCode, FuelType $fuelType): ?FipeEntry;

  public function findHistorical(
    FipeCode $fipeCode,
    FuelType $fuelType,
    int $monthsBack = 12
  ): array;

  public function findByModelYear(int $modelYear, ?YearMonth $referenceMonth = null): array;

  public function findPriceHistory(
    FipeCode $fipeCode,
    FuelType $fuelType,
    YearMonth $startMonth,
    YearMonth $endMonth
  ): array;

  public function delete(FipeEntry $fipeEntry): void;

  public function exists(FipeCode $fipeCode, YearMonth $month, FuelType $fuelType): bool;

  /**
   * Busca entradas FIPE mais recentes por categoria
   */
  public function findRecentByCategory(string $category, int $limit = 50): array;

  /**
   * Calcula variação de preço entre dois meses
   */
  public function calculatePriceVariation(
    FipeCode $fipeCode,
    FuelType $fuelType,
    YearMonth $fromMonth,
    YearMonth $toMonth
  ): ?float;

  /**
   * Busca todas as entradas FIPE com paginação e filtros
   * 
   * @param int $page Número da página
   * @param int $limit Limite de resultados por página
   * @param array $filters Filtros opcionais (fipeCode, brand, model, referenceMonth)
   * @return array Array de FipeEntry
   */
  public function getAll(int $page, int $limit, array $filters = []): array;

  /**
   * Conta o total de entradas FIPE com filtros aplicados
   * 
   * @param array $filters Filtros opcionais (fipeCode, brand, model, referenceMonth)
   * @return int Total de registros
   */
  public function countAll(array $filters = []): int;
}
