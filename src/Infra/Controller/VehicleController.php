<?php

namespace App\Infra\Controller;

use App\Application\DTO\Vehicle\VehicleDTO;
use App\Application\UseCase\Vehicle\CompareVehicleWithFipeUseCase;
use App\Application\UseCase\Vehicle\DeleteVehicleUseCase;
use App\Application\UseCase\Vehicle\GetVehicleUseCase;
use App\Application\UseCase\Vehicle\ListVehiclesUseCase;
use App\Application\UseCase\Vehicle\RegisterVehicleUseCase;
use App\Application\UseCase\Vehicle\UpdateVehicleUseCase;
use App\Domain\Repositories\VehicleRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class VehicleController extends AbstractController
{
  #[Route('/vehicles', name: 'app_register_vehicle', methods: ['POST'])]
  #[IsGranted('VEHICLE_CREATE')]
  public function register(Request $request, RegisterVehicleUseCase $registerVehicleUseCase): JsonResponse
  {
    try {
      $data = json_decode($request->getContent() ?: '{}', true);
      $currentUser = $this->getUser();

      if (!$currentUser) {
        return $this->json(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
      }

      $vehicleDto = $this->buildVehicleDTO($data, $currentUser);
      $result = $registerVehicleUseCase->execute($vehicleDto);
      return $this->json($result, JsonResponse::HTTP_CREATED);
    } catch (\InvalidArgumentException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: JsonResponse::HTTP_BAD_REQUEST);
    }
  }

  #[Route('/vehicles', name: 'app_list_vehicles', methods: ['GET'])]
  public function list(Request $request, ListVehiclesUseCase $listVehiclesUseCase): JsonResponse
  {
    try {
      [$page, $limit] = $this->getPaginateParams($request);
      $filters = $this->getFilters($request);

      $result = $listVehiclesUseCase->execute([
        'page' => $page,
        'limit' => $limit,
        'filters' => $filters
      ]);

      return $this->json($result);
    } catch (\InvalidArgumentException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: JsonResponse::HTTP_BAD_REQUEST);
    }
  }

  #[Route('/vehicles/{id}', name: 'app_get_vehicle', methods: ['GET'])]
  public function get(string $id, GetVehicleUseCase $getVehicleUseCase): JsonResponse
  {
    try {
      $result = $getVehicleUseCase->execute($id);
      return $this->json($result);
    } catch (\InvalidArgumentException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: JsonResponse::HTTP_NOT_FOUND);
    }
  }

  #[Route('/vehicles/{id}', name: 'app_update_vehicle', methods: ['PUT', 'PATCH'])]
  public function update(string $id, Request $request, UpdateVehicleUseCase $updateVehicleUseCase, VehicleRepositoryInterface $vehicleRepository): JsonResponse
  {
    try {
      $vehicle = $vehicleRepository->findById($id);
      if (!$vehicle) {
        return $this->json(['error' => 'Vehicle not found'], JsonResponse::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted('VEHICLE_EDIT', $vehicle);
      $data = json_decode($request->getContent() ?: '{}', true);
      $currentUser = $this->getUser();
      $vehicleDto = $this->buildVehicleDTO($data, $currentUser);
      $result = $updateVehicleUseCase->execute(['id' => $id, 'data' => $vehicleDto]);
      return $this->json($result);
    } catch (\InvalidArgumentException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: JsonResponse::HTTP_BAD_REQUEST);
    }
  }

  #[Route('/vehicles/{id}', name: 'app_delete_vehicle', methods: ['DELETE'])]
  public function delete(string $id, DeleteVehicleUseCase $deleteVehicleUseCase, VehicleRepositoryInterface $vehicleRepository): JsonResponse
  {
    try {
      $vehicle = $vehicleRepository->findById($id);
      if (!$vehicle) {
        return $this->json(['error' => 'Vehicle not found'], JsonResponse::HTTP_NOT_FOUND);
      }
      $this->denyAccessUnlessGranted('VEHICLE_DELETE', $vehicle);
      $result = $deleteVehicleUseCase->execute($id);
      return $this->json($result);
    } catch (\InvalidArgumentException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: JsonResponse::HTTP_BAD_REQUEST);
    }
  }

  #[Route('/vehicles/{id}/compare', name: 'app_compare_vehicle', methods: ['GET'])]
  public function compare(string $id, CompareVehicleWithFipeUseCase $compareUseCase): JsonResponse
  {
    try {
      $result = $compareUseCase->execute($id);
      return $this->json($result);
    } catch (\InvalidArgumentException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: JsonResponse::HTTP_NOT_FOUND);
    }
  }

  private function buildVehicleDTO(array $data, $currentUser): VehicleDTO
  {
    return new VehicleDTO(
      brand: $data['brand'] ?? '',
      model: $data['model'] ?? '',
      version: $data['version'] ?? '',
      category: $data['category'] ?? 'car',
      year: (int)($data['year'] ?? 0),
      priceValue: (float)($data['priceValue'] ?? 0),
      priceCurrency: $data['priceCurrency'] ?? 'BRL',
      mileageValue: (int)($data['mileageValue'] ?? 0),
      mileageUnit: 'km',
      fuelType: $data['fuelType'] ?? 'gasoline',
      transmission: $data['transmission'] ?? 'manual',
      fipeCode: $data['fipeCode'] ?? '',
      vin: $data['vin'] ?? null,
      description: $data['description'] ?? null,
      images: $data['images'] ?? [],
      owner: $currentUser
    );
  }

  private function getPaginateParams(Request $request): array
  {
    $page = max(1, (int)$request->query->get('page', 1));
    $limit = max(1, min(100, (int)$request->query->get('limit', 10)));

    return [$page, $limit];
  }

  private function getFilters(Request $request): array
  {
    $filters = [];

    if ($request->query->has('price_min')) {
      $filters['price_min'] = (float) $request->query->get('price_min');
    }
    if ($request->query->has('price_max')) {
      $filters['price_max'] = (float) $request->query->get('price_max');
    }
    if ($request->query->has('year_min')) {
      $filters['year_min'] = (int) $request->query->get('year_min');
    }
    if ($request->query->has('year_max')) {
      $filters['year_max'] = (int) $request->query->get('year_max');
    }
    if ($request->query->has('brand')) {
      $filters['brand'] = $request->query->get('brand');
    }
    if ($request->query->has('model')) {
      $filters['model'] = $request->query->get('model');
    }
    if ($request->query->has('fuelType')) {
      $filters['fuelType'] = $request->query->get('fuelType');
    }

    return $filters;
  }
}
