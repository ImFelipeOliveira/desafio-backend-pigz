<?php

declare(strict_types=1);

namespace App\Application\UseCase\Fipe;

use App\Application\UseCase\UseCaseInterface;
use App\Domain\Entity\FipeEntry;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\ValueObjects\FuelType;
use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\YearMonth;
use App\Domain\Repositories\FipeEntryRepositoryInterface;
use App\Domain\Services\FipeServiceInterface;
use App\Infra\Adapter\Fipe\FipeApiClient;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Use Case para sincronizar dados da API FIPE externa
 * Busca informações atualizadas e persiste no banco
 */
class SyncFipeDataUseCase implements UseCaseInterface
{
  public function __construct(
    private readonly FipeServiceInterface $fipeService,
    private readonly FipeEntryRepositoryInterface $fipeRepository
  ) {}

  /**
   * @param array{vehicleType: string, brandCode: string, modelCode: string, yearCode: string} $input
   */
  public function execute(mixed $input): array
  {
    $this->validateInput($input);

    try {
      $vehicleData = $this->fetchVehicleDataFromApi($input);
      $fipeCode = $this->extractFipeCode($vehicleData);
      $referenceMonth = YearMonth::current();
      $fuelType = $this->extractFuelType($input['yearCode']);

      if ($this->fipeEntryAlreadyExists($fipeCode, $referenceMonth, $fuelType)) {
        return $this->buildAlreadyExistsResponse($fipeCode);
      }

      $fipeEntry = $this->buildFipeEntry($vehicleData, $fipeCode, $referenceMonth, $fuelType);
      $this->fipeRepository->save($fipeEntry);

      return $this->buildSuccessResponse($fipeEntry, $vehicleData);
    } catch (\Exception $e) {
      throw new \InvalidArgumentException(
        sprintf('Failed to sync FIPE data: %s', $e->getMessage()),
        JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }

  private function validateInput(mixed $input): void
  {
    if (!is_array($input) || !isset($input['vehicleType'], $input['brandCode'], $input['modelCode'], $input['yearCode'])) {
      throw new \InvalidArgumentException(
        'Invalid input: vehicleType, brandCode, modelCode, and yearCode are required',
        JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }

  private function fetchVehicleDataFromApi(array $input): array
  {
    $vehicleData = $this->fipeService->getVehicleValue(
      $input['vehicleType'],
      $input['brandCode'],
      $input['modelCode'],
      $input['yearCode']
    );

    if (empty($vehicleData)) {
      throw new \RuntimeException('No data returned from FIPE API');
    }

    return $vehicleData;
  }

  private function extractFipeCode(array $vehicleData): string
  {
    $fipeCode = FipeApiClient::extractFipeCode($vehicleData);

    if ($fipeCode === null) {
      throw new \RuntimeException('FIPE code not found in response');
    }

    return $fipeCode;
  }

  private function extractFuelType(string $yearCode): FuelType
  {
    $fuelCode = explode('-', $yearCode)[1] ?? '1';
    return $this->mapFuelCodeToType($fuelCode);
  }

  private function fipeEntryAlreadyExists(string $fipeCode, YearMonth $referenceMonth, FuelType $fuelType): bool
  {
    return $this->fipeRepository->exists(new FipeCode($fipeCode), $referenceMonth, $fuelType);
  }

  private function buildFipeEntry(array $vehicleData, string $fipeCode, YearMonth $referenceMonth, FuelType $fuelType): FipeEntry
  {
    $price = FipeApiClient::parseFipePrice($vehicleData['Valor'] ?? $vehicleData['valor'] ?? '0');

    $specification = new VehicleSpecification(
      $vehicleData['Marca'] ?? $vehicleData['marca'] ?? 'Unknown',
      $vehicleData['Modelo'] ?? $vehicleData['modelo'] ?? 'Unknown',
      $vehicleData['TipoVeiculo'] ?? $vehicleData['categoria'] ?? 'car',
      null
    );

    return new FipeEntry(
      new FipeCode($fipeCode),
      $specification,
      $fuelType,
      new Price($price),
      $referenceMonth,
      (int) ($vehicleData['AnoModelo'] ?? $vehicleData['anoModelo'] ?? 0)
    );
  }

  private function buildAlreadyExistsResponse(string $fipeCode): array
  {
    return [
      'message' => 'FIPE entry already exists for this month',
      'fipe_code' => $fipeCode,
    ];
  }

  private function buildSuccessResponse(FipeEntry $fipeEntry, array $vehicleData): array
  {
    return [
      'message' => 'FIPE data synchronized successfully',
      'fipe_entry' => $fipeEntry,
      'source_data' => $vehicleData,
    ];
  }

  private function mapFuelCodeToType(string $code): FuelType
  {
    return match ($code) {
      '1' => FuelType::GASOLINE,
      '2' => FuelType::ETHANOL,
      '3' => FuelType::DIESEL,
      default => FuelType::FLEX,
    };
  }
}
