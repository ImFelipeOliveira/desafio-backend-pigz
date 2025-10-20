<?php

declare(strict_types=1);

namespace App\Application\UseCase\Fipe;

use App\Application\DTO\Fipe\FipeEntryDTO;
use App\Application\UseCase\UseCaseInterface;
use App\Domain\Entity\FipeEntry;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\ValueObjects\FuelType;
use App\Domain\Entity\ValueObjects\Price;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\YearMonth;
use App\Domain\Repositories\FipeEntryRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegisterFipeEntryUseCase implements UseCaseInterface
{
  public function __construct(
    private readonly FipeEntryRepositoryInterface $fipeRepository
  ) {}

  /**
   * @param FipeEntryDTO $input
   */
  public function execute(mixed $input): array
  {
    if (!$input instanceof FipeEntryDTO) {
      throw new \InvalidArgumentException(
        'Invalid input type',
        JsonResponse::HTTP_BAD_REQUEST
      );
    }

    try {
      $fipeEntry = new FipeEntry(
        new FipeCode($input->getFipeCode()),
        new VehicleSpecification(
          $input->getBrand(),
          $input->getModel(),
          $input->getCategory(),
          $input->getVersion()
        ),
        FuelType::from($input->getFuelType()),
        new Price($input->getPriceValue(), $input->getPriceCurrency()),
        YearMonth::fromString($input->getReferenceMonth()),
        $input->getModelYear()
      );

      $this->fipeRepository->save($fipeEntry);

      return [$fipeEntry->toArray()];
    } catch (\Exception $e) {
      throw new \InvalidArgumentException(
        sprintf('Failed to create FIPE entry: %s', $e->getMessage()),
        JsonResponse::HTTP_BAD_REQUEST
      );
    }
  }
}
