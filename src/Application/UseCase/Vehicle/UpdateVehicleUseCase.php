<?php

declare(strict_types=1);

namespace App\Application\UseCase\Vehicle;

use App\Application\DTO\Vehicle\VehicleDTO;
use App\Application\UseCase\UseCaseInterface;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\ValueObjects\FuelType;
use App\Domain\Entity\ValueObjects\Mileage;
use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\TransmissionType;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\VIN;
use App\Domain\Entity\ValueObjects\Year;
use App\Domain\Repositories\VehicleRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class UpdateVehicleUseCase implements UseCaseInterface
{
  public function __construct(
    private readonly VehicleRepositoryInterface $vehicleRepository
  ) {}

  /**
   * @param array{id: string, data: VehicleDTO} $input
   */
  public function execute(mixed $input): array
  {
    $this->validateInput($input);

    $vehicleId = $input['id'];
    $dto = $input['data'];

    $vehicle = $this->findVehicle($vehicleId);

    try {
      $this->updateVehicleData($vehicle, $dto);
      $this->vehicleRepository->update($vehicle);

      return [$vehicle->toArray()];
    } catch (\Exception $e) {
      throw new \InvalidArgumentException(
        sprintf('Failed to update vehicle: %s', $e->getMessage()),
        JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }

  private function validateInput(mixed $input): void
  {
    if (!is_array($input) || !isset($input['id'], $input['data'])) {
      throw new \InvalidArgumentException(
        'Invalid input: expected array with id and data',
        JsonResponse::HTTP_BAD_REQUEST
      );
    }

    if (!$input['data'] instanceof VehicleDTO) {
      throw new \InvalidArgumentException(
        'Invalid data type',
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

  private function updateVehicleData($vehicle, VehicleDTO $dto): void
  {
    $this->updatePrice($vehicle, $dto);
    $this->updateMileage($vehicle, $dto);
    $this->updateDescription($vehicle, $dto);
    $this->updateImages($vehicle, $dto);
    $this->updateSpecification($vehicle, $dto);
    $this->updateYear($vehicle, $dto);
    $this->updateFuelType($vehicle, $dto);
    $this->updateTransmission($vehicle, $dto);
    $this->updateFipeCode($vehicle, $dto);
    $this->updateVin($vehicle, $dto);
  }

  private function updateSpecification($vehicle, VehicleDTO $dto): void
  {
    $reflection = new \ReflectionClass($vehicle);
    $property = $reflection->getProperty('specification');
    $property->setAccessible(true);
    $property->setValue($vehicle, new VehicleSpecification(
      $dto->getBrand(),
      $dto->getModel(),
      $dto->getCategory(),
      $dto->getVersion()
    ));
  }

  private function updateYear($vehicle, VehicleDTO $dto): void
  {
    $reflection = new \ReflectionClass($vehicle);
    $property = $reflection->getProperty('year');
    $property->setAccessible(true);
    $property->setValue($vehicle, new Year($dto->getYear()));
  }

  private function updateFuelType($vehicle, VehicleDTO $dto): void
  {
    $reflection = new \ReflectionClass($vehicle);
    $property = $reflection->getProperty('fuelType');
    $property->setAccessible(true);
    $property->setValue($vehicle, FuelType::from($dto->getFuelType()));
  }

  private function updateTransmission($vehicle, VehicleDTO $dto): void
  {
    $reflection = new \ReflectionClass($vehicle);
    $property = $reflection->getProperty('transmission');
    $property->setAccessible(true);
    $property->setValue($vehicle, TransmissionType::from($dto->getTransmission()));
  }

  private function updateFipeCode($vehicle, VehicleDTO $dto): void
  {
    $reflection = new \ReflectionClass($vehicle);
    $property = $reflection->getProperty('fipeCode');
    $property->setAccessible(true);
    $property->setValue($vehicle, new FipeCode($dto->getFipeCode()));
  }

  private function updateVin($vehicle, VehicleDTO $dto): void
  {
    if (!$dto->getVin()) {
      return;
    }
    $reflection = new \ReflectionClass($vehicle);
    $property = $reflection->getProperty('vin');
    $property->setAccessible(true);
    $property->setValue($vehicle, new VIN($dto->getVin()));
  }

  private function updatePrice($vehicle, VehicleDTO $dto): void
  {
    $vehicle->updatePrice(new Price($dto->getPriceValue()));
  }

  private function updateMileage($vehicle, VehicleDTO $dto): void
  {
    $vehicle->updateMileage(new Mileage($dto->getMileageValue()));
  }

  private function updateDescription($vehicle, VehicleDTO $dto): void
  {
    if ($dto->getDescription()) {
      $vehicle->updateDescription($dto->getDescription());
    }
  }

  private function updateImages($vehicle, VehicleDTO $dto): void
  {
    if (!$dto->getImages()) {
      return;
    }

    foreach ($dto->getImages() as $image) {
      if (!$this->vehicleHasImage($vehicle, $image)) {
        $vehicle->addImage($image);
      }
    }
  }

  private function vehicleHasImage($vehicle, string $image): bool
  {
    return in_array($image, $vehicle->getImages(), true);
  }
}
