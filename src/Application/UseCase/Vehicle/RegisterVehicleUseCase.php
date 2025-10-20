<?php

declare(strict_types=1);

namespace App\Application\UseCase\Vehicle;

use App\Application\UseCase\UseCaseInterface;
use App\Application\DTO\Vehicle\VehicleDTO;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Repositories\VehicleRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\Year;
use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\Mileage;
use App\Domain\Entity\ValueObjects\VIN;
use App\Domain\Entity\ValueObjects\FuelType;
use App\Domain\Entity\ValueObjects\TransmissionType;
use App\Domain\Entity\Vehicle;
use App\Domain\Repositories\UserRepositoryInterface;
use Symfony\Component\Uid\Uuid;

class RegisterVehicleUseCase implements UseCaseInterface
{

  public function __construct(
    private VehicleRepositoryInterface $vehicleRepository,
    private UserRepositoryInterface $userRepository
  ) {}

  /**
   * @param VehicleDTO|mixed $input
   */
  public function execute(mixed $input): array
  {
    $this->verifyInputInstanceOf($input);
    $user = $this->userRepository->findByEmail($input->getOwner()->getUserIdentifier());
    $vehicle = $this->buildVehicleEntity($input, $user->getId());
    $this->vehicleRepository->save($vehicle);
    return [$vehicle->toArray()];
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

  private function buildVehicleEntity(VehicleDTO $input, string $userId): Vehicle
  {
    try {
      return Vehicle::register(
        (string) Uuid::v7(),
        $this->getSpecification($input),
        new Year($input->getYear()),
        new Price($input->getPriceValue()),
        new Mileage($input->getMileageValue()),
        FuelType::from($input->getFuelType()),
        TransmissionType::from($input->getTransmission()),
        new FipeCode($input->getFipeCode()),
        $userId,
        $input->getVin() ? new VIN($input->getVin()) : null,
        $input->getDescription(),
        $input->getImages()
      );
    } catch (\Exception $e) {
      throw new \InvalidArgumentException(
        $e->getMessage(),
        code: JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }
}
