<?php

namespace App\Infra\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Application\UseCase\Vehicle\RegisterVehicleUseCase;
use App\Application\DTO\Vehicle\VehicleDTO;
use Symfony\Component\Security\Core\User\UserInterface;

class VehicleController extends AbstractController
{

  #[Route('/vehicles', name: 'app_register_vehicle', methods: ['POST'])]
  public function register(Request $request, RegisterVehicleUseCase $registerVehicleUseCase): JsonResponse
  {
    try {
      $data = json_decode($request->getContent() ?: '{}', true);
      $currentUser = $this->getUser();
      $vehicleDto = $this->buildEntry($data, $currentUser);
      $result = $registerVehicleUseCase->execute($vehicleDto);
      return $this->json($result, JsonResponse::HTTP_CREATED);
    } catch (\InvalidArgumentException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode());
    }
  }

  private function buildEntry(mixed $data, UserInterface $currentUser): VehicleDTO
  {
    return new VehicleDTO(
      $data['brand'] ?? '',
      $data['model'] ?? '',
      $data['category'] ?? '',
      $data['version'] ?? '',
      (int)($data['year'] ?? 0),
      (float)($data['price'] ?? 0),
      (int)($data['mileage'] ?? 0),
      $data['fipeCode'] ?? '',
      $data['vin'] ?? null,
      $data['description'] ?? null,
      $data['images'] ?? [],
      $currentUser->getUserIdentifier()
    );
  }
}
