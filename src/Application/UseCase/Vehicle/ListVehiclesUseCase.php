<?php

namespace App\Application\UseCase\Vehicle;

use App\Application\UseCase\UseCaseInterface;
use App\Domain\Repositories\VehicleRepositoryInterface;

class ListVehiclesUseCase implements UseCaseInterface
{
  public function __construct(private VehicleRepositoryInterface $vehicleRepository) {}

  /**
   * @param mixed $input Array com 'page', 'limit' e 'filters'
   */
  public function execute(mixed $input): array
  {
    if (!is_array($input)) {
      throw new \InvalidArgumentException('Input must be an array');
    }

    $page = $input['page'] ?? 1;
    $limit = $input['limit'] ?? 10;
    $filters = $input['filters'] ?? [];

    // Buscar veículos com filtros e paginação
    $vehicles = $this->vehicleRepository->findByFilters($filters, $page, $limit);
    $total = $this->vehicleRepository->countByFilters($filters);

    return [
      'data' => array_map(fn($vehicle) => $this->formatVehicle($vehicle), $vehicles),
      'pagination' => [
        'current_page' => $page,
        'per_page' => $limit,
        'total' => $total,
        'total_pages' => (int) ceil($total / $limit),
      ]
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
      'mileage' => $vehicle->getMileage()->getKilometers(),
      'fuelType' => $vehicle->getFuelType()->value,
      'transmission' => $vehicle->getTransmission()->value,
      'fipeCode' => $vehicle->getFipeCode()->getCode(),
      'vin' => $vehicle->getVin()?->getNumber(),
      'status' => $vehicle->getStatus()->value,
      'ownerId' => $vehicle->getOwnerId(),
      'createdAt' => $vehicle->getCreatedAt()->format('Y-m-d H:i:s'),
    ];
  }
}
