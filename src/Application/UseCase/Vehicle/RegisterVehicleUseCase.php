<?php

declare(strict_types=1);

namespace App\Application\UseCase\Vehicle;

use App\Application\UseCase\UseCaseInterface\UseCaseInterface;
use App\Application\DTO\Vehicle\VehicleDTO;
use App\Domain\Repositories\VehicleRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Domain\Entity\ValueObjects\VehicleSpecification;

class RegisterVehicleUseCase implements UseCaseInterface
{

  public function __construct(private VehicleRepositoryInterface $vehicleRepository) {}

  /**
   * @param VehicleDTO|mixed
   */
  public function execute(mixed $input): array
  {
    $this->verifyInputInstanceOf($input);
    $vehicle = new Vehicle()::register(
      $this->getSpecification($input),
    )
    return [];
  }

  private function verifyInputInstanceOf(mixed $input): void
  {
    if (!$input instanceof VehicleDTO) {
      throw new \InvalidArgumentException(
        'Invalid input type',
        code: JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }

  private function getSpecification(VehicleDTO $input): VehicleSpecification
  {
    return new VehicleSpecification(
      $input->getBrand(),
      $input->getModel(),
      $input->getCategory(),
      $input->getVersion()
    );
  }
}
