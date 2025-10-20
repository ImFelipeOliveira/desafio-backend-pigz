<?php

declare(strict_types=1);

namespace App\Application\UseCase\Vehicle;

use App\Application\UseCase\UseCaseInterface;
use App\Domain\Repositories\FipeEntryRepositoryInterface;
use App\Domain\Repositories\VehicleRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Use Case para comparar veículo com tabela FIPE
 * Retorna diferença de preço e informações relevantes
 */
class CompareVehicleWithFipeUseCase implements UseCaseInterface
{
  public function __construct(
    private readonly VehicleRepositoryInterface $vehicleRepository,
    private readonly FipeEntryRepositoryInterface $fipeRepository
  ) {}

  /**
   * @param string $input Vehicle ID
   * @return array Comparison data with vehicle, FIPE entry, and price difference
   */
  public function execute(mixed $input): array
  {
    $this->validateInput($input);

    $vehicle = $this->findVehicle($input);
    $fipeEntry = $this->findLatestFipeEntry($vehicle);

    if (!$fipeEntry) {
      return $this->buildNoFipeDataResponse($vehicle);
    }

    $comparison = $this->calculatePriceComparison($vehicle, $fipeEntry);

    return $this->buildComparisonResponse($vehicle, $fipeEntry, $comparison);
  }

  private function validateInput(mixed $input): void
  {
    if (!is_string($input) || empty($input)) {
      throw new \InvalidArgumentException(
        'Vehicle ID must be a non-empty string',
        JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }

  private function findVehicle(string $vehicleId)
  {
    $vehicle = $this->vehicleRepository->findById($vehicleId);

    if (!$vehicle) {
      throw new \InvalidArgumentException(
        'Vehicle not found',
        JsonResponse::HTTP_NOT_FOUND
      );
    }

    return $vehicle;
  }

  private function findLatestFipeEntry($vehicle)
  {
    return $this->fipeRepository->findLatest(
      $vehicle->getFipeCode(),
      $vehicle->getFuelType()
    );
  }

  private function buildNoFipeDataResponse($vehicle): array
  {
    return [
      'vehicle' => $this->formatVehicle($vehicle),
      'fipe' => null,
      'comparison' => null,
      'message' => 'No FIPE data found for this vehicle'
    ];
  }

  private function calculatePriceComparison($vehicle, $fipeEntry): array
  {
    $vehiclePrice = $vehicle->getPrice()->getValue();
    $fipePrice = $fipeEntry->getPrice()->getValue();
    $difference = $this->calculatePriceDifference($vehiclePrice, $fipePrice);
    $percentageDifference = $this->calculatePercentageDifference($difference, $fipePrice);

    return [
      'vehicle_price' => $vehiclePrice,
      'fipe_price' => $fipePrice,
      'difference' => $difference,
      'percentage_difference' => round($percentageDifference, 2),
      'status' => $this->getComparisonStatus($percentageDifference),
      'recommendation' => $this->getRecommendation($percentageDifference),
    ];
  }

  private function calculatePriceDifference(float $vehiclePrice, float $fipePrice): float
  {
    return $vehiclePrice - $fipePrice;
  }

  private function calculatePercentageDifference(float $difference, float $fipePrice): float
  {
    if ($fipePrice <= 0) {
      return 0.0;
    }

    return ($difference / $fipePrice) * 100;
  }

  private function buildComparisonResponse($vehicle, $fipeEntry, array $comparison): array
  {
    return [
      'vehicle' => $this->formatVehicle($vehicle),
      'fipe' => $this->formatFipeEntry($fipeEntry),
      'comparison' => $comparison
    ];
  }

  private function formatVehicle($vehicle): array
  {
    return [
      'id' => $vehicle->getId(),
      'brand' => $vehicle->getSpecification()->getBrand(),
      'model' => $vehicle->getSpecification()->getModel(),
      'version' => $vehicle->getSpecification()->getVersion(),
      'year' => $vehicle->getYear()->getValue(),
      'price' => $vehicle->getPrice()->getValue(),
      'currency' => $vehicle->getPrice()->getCurrency(),
      'fuel_type' => $vehicle->getFuelType()->getLabel(),
      'mileage' => $vehicle->getMileage()->getKilometers(),
      'fipe_code' => $vehicle->getFipeCode()->getCode(),
    ];
  }

  private function formatFipeEntry($fipeEntry): array
  {
    return [
      'id' => $fipeEntry->getId()->toRfc4122(),
      'fipe_code' => $fipeEntry->getFipeCode()->getCode(),
      'price' => $fipeEntry->getPrice()->getValue(),
      'currency' => $fipeEntry->getPrice()->getCurrency(),
      'reference_month' => $fipeEntry->getReferenceMonth()->format(),
      'model_year' => $fipeEntry->getModelYear(),
      'fuel_type' => $fipeEntry->getFuelType()->getLabel(),
    ];
  }

  private function getComparisonStatus(float $percentageDifference): string
  {
    return match (true) {
      $percentageDifference < -10 => 'below_fipe',
      $percentageDifference > 10 => 'above_fipe',
      default => 'within_fipe',
    };
  }

  private function getRecommendation(float $percentageDifference): string
  {
    return match (true) {
      $percentageDifference < -20 => 'Excellent deal! Price significantly below FIPE table.',
      $percentageDifference < -10 => 'Good deal. Price below FIPE table.',
      $percentageDifference < -5 => 'Fair price, slightly below FIPE.',
      $percentageDifference <= 5 => 'Price aligned with FIPE table.',
      $percentageDifference <= 10 => 'Price slightly above FIPE table.',
      $percentageDifference <= 20 => 'Price above FIPE table. Negotiate.',
      default => 'Price significantly above FIPE table. Avoid or negotiate heavily.',
    };
  }
}
