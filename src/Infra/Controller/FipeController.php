<?php

declare(strict_types=1);

namespace App\Infra\Controller;

use App\Application\DTO\Fipe\FipeEntryDTO;
use App\Application\UseCase\Fipe\ListFipeEntriesUseCase;
use App\Application\UseCase\Fipe\RegisterFipeEntryUseCase;
use App\Application\UseCase\Fipe\SyncFipeDataUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/fipe')]
class FipeController extends AbstractController
{
  #[Route('', name: 'app_list_fipe', methods: ['GET'])]
  public function list(Request $request, ListFipeEntriesUseCase $listUseCase): JsonResponse
  {
    try {
      $page = max(1, (int) $request->query->get('page', 1));
      $limit = max(1, min(100, (int) $request->query->get('limit', 10)));

      $filters = [];
      if ($request->query->has('fipeCode')) {
        $filters['fipeCode'] = $request->query->get('fipeCode');
      }
      if ($request->query->has('brand')) {
        $filters['brand'] = $request->query->get('brand');
      }
      if ($request->query->has('model')) {
        $filters['model'] = $request->query->get('model');
      }
      if ($request->query->has('referenceMonth')) {
        $filters['referenceMonth'] = $request->query->get('referenceMonth');
      }

      $result = $listUseCase->execute([
        'page' => $page,
        'limit' => $limit,
        'filters' => $filters
      ]);

      return $this->json($result);
    } catch (\Exception $e) {
      return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
    }
  }

  #[Route('', name: 'app_register_fipe', methods: ['POST'])]
  #[IsGranted('FIPE_CREATE')]
  public function register(Request $request, RegisterFipeEntryUseCase $registerUseCase): JsonResponse
  {
    try {
      $data = json_decode($request->getContent() ?: '{}', true);

      if (!$data) {
        return $this->json(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
      }

      $dto = new FipeEntryDTO(
        $data['fipeCode'] ?? '',
        $data['brand'] ?? '',
        $data['model'] ?? '',
        $data['category'] ?? 'car',
        $data['version'] ?? null,
        $data['fuelType'] ?? 'gasoline',
        (float) ($data['priceValue'] ?? 0),
        $data['priceCurrency'] ?? 'BRL',
        $data['referenceMonth'] ?? date('Y-m'),
        (int) ($data['modelYear'] ?? date('Y'))
      );

      $result = $registerUseCase->execute($dto);

      return $this->json($result, JsonResponse::HTTP_CREATED);
    } catch (\InvalidArgumentException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: JsonResponse::HTTP_BAD_REQUEST);
    }
  }

  #[Route('/sync', name: 'app_sync_fipe', methods: ['POST'])]
  #[IsGranted('FIPE_SYNC')]
  public function sync(Request $request, SyncFipeDataUseCase $syncUseCase): JsonResponse
  {
    try {
      $data = json_decode($request->getContent() ?: '{}', true);

      if (!$data) {
        return $this->json(['error' => 'Invalid JSON'], JsonResponse::HTTP_BAD_REQUEST);
      }

      $result = $syncUseCase->execute([
        'vehicleType' => $data['vehicleType'] ?? 'carros',
        'brandCode' => $data['brandCode'] ?? '',
        'modelCode' => $data['modelCode'] ?? '',
        'yearCode' => $data['yearCode'] ?? '',
      ]);

      return $this->json($result, JsonResponse::HTTP_CREATED);
    } catch (\InvalidArgumentException $e) {
      return $this->json(['error' => $e->getMessage()], $e->getCode() ?: JsonResponse::HTTP_BAD_REQUEST);
    }
  }
}
