<?php

declare(strict_types=1);

namespace App\Application\UseCase\Vehicle;

use App\Application\DTO\Vehicle\VehicleDTO;
use App\Application\UseCase\UseCaseInterface;
use App\Domain\Entity\ValueObjects\Mileage;
use App\Domain\Entity\ValueObjects\Price;
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
