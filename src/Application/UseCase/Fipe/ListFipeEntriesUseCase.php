<?php

declare(strict_types=1);

namespace App\Application\UseCase\Fipe;

use App\Application\UseCase\UseCaseInterface;
use App\Domain\Repositories\FipeEntryRepositoryInterface;

class ListFipeEntriesUseCase implements UseCaseInterface
{
  public function __construct(
    private readonly FipeEntryRepositoryInterface $fipeRepository
  ) {}

  /**
   * @param array{page: int, limit: int, filters: array} $input
   */
  public function execute(mixed $input): array
  {
    $page = $input['page'] ?? 1;
    $limit = $input['limit'] ?? 10;
    $filters = $input['filters'] ?? [];

    $entries = $this->fipeRepository->getAll($page, $limit, $filters);
    $total = $this->fipeRepository->countAll($filters);
    $totalPages = (int) ceil($total / $limit);

    return [
      'data' => $entries,
      'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'totalPages' => $totalPages,
      ]
    ];
  }
}
