<?php

declare(strict_types=1);

namespace App\Application\UseCase\Vehicle;

use App\Application\UseCase\UseCaseInterface;
use App\Domain\Repositories\VehicleRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class DeleteVehicleUseCase implements UseCaseInterface
{
  public function __construct(
    private readonly VehicleRepositoryInterface $vehicleRepository
  ) {}

  /**
   * @param string $input Vehicle ID
   */
  public function execute(mixed $input): array
  {
    if (!is_string($input) || empty($input)) {
      throw new \InvalidArgumentException(
        'Vehicle ID must be a non-empty string',
        JsonResponse::HTTP_BAD_REQUEST
      );
    }

    $vehicle = $this->vehicleRepository->findById($input);

    if (!$vehicle) {
      throw new \InvalidArgumentException(
        'Vehicle not found',
        JsonResponse::HTTP_NOT_FOUND
      );
    }

    $vehicle->deactivate();
    $this->vehicleRepository->update($vehicle);

    return ['message' => 'Vehicle deleted successfully'];
  }
}
