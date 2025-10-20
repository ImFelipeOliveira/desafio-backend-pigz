<?php

declare(strict_types=1);

namespace App\Infra\Persistence\Doctrine\Repository;

use App\Domain\Entity\FipeEntry;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\ValueObjects\FuelType;
use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\ValueObjects\YearMonth;
use App\Domain\Repositories\FipeEntryRepositoryInterface;
use App\Infra\Persistence\Doctrine\Entity\FipeEntryEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FipeEntryRepository extends ServiceEntityRepository implements FipeEntryRepositoryInterface
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, FipeEntryEntity::class);
  }

  public function save(FipeEntry $fipeEntry): void
  {
    $entity = FipeEntryEntity::fromDomain($fipeEntry);
    $this->getEntityManager()->persist($entity);
    $this->getEntityManager()->flush();
  }

  public function findById(string $id): ?FipeEntry
  {
    $entity = $this->find($id);
    return $entity ? $entity->toDomain() : null;
  }

  public function findByFipeCode(FipeCode $fipeCode): array
  {
    $entities = $this->findBy(['fipeCode' => $fipeCode->getCode()]);
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findByReferenceMonth(YearMonth $month): array
  {
    $entities = $this->findBy(['referenceMonth' => $month->format()]);
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findBySpecification(VehicleSpecification $specification): array
  {
    $entities = $this->findBy([
      'brand' => $specification->getBrand(),
      'model' => $specification->getModel(),
    ]);
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findLatest(FipeCode $fipeCode, FuelType $fuelType): ?FipeEntry
  {
    $entity = $this->createQueryBuilder('f')
      ->where('f.fipeCode = :code')
      ->andWhere('f.fuelType = :fuel')
      ->setParameter('code', $fipeCode->getCode())
      ->setParameter('fuel', $fuelType->value)
      ->orderBy('f.referenceMonth', 'DESC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

    return $entity ? $entity->toDomain() : null;
  }

  public function findHistorical(FipeCode $fipeCode, FuelType $fuelType, int $monthsBack = 12): array
  {
    $startMonth = (new \DateTimeImmutable())->modify("-{$monthsBack} months")->format('Y-m');

    $entities = $this->createQueryBuilder('f')
      ->where('f.fipeCode = :code')
      ->andWhere('f.fuelType = :fuel')
      ->andWhere('f.referenceMonth >= :startMonth')
      ->setParameter('code', $fipeCode->getCode())
      ->setParameter('fuel', $fuelType->value)
      ->setParameter('startMonth', $startMonth)
      ->orderBy('f.referenceMonth', 'DESC')
      ->getQuery()
      ->getResult();

    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findByModelYear(int $modelYear, ?YearMonth $referenceMonth = null): array
  {
    $qb = $this->createQueryBuilder('f')
      ->where('f.modelYear = :year')
      ->setParameter('year', $modelYear);

    if ($referenceMonth) {
      $qb->andWhere('f.referenceMonth = :month')
        ->setParameter('month', $referenceMonth->format());
    }

    $entities = $qb->getQuery()->getResult();
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findPriceHistory(
    FipeCode $fipeCode,
    FuelType $fuelType,
    YearMonth $startMonth,
    YearMonth $endMonth
  ): array {
    $entities = $this->createQueryBuilder('f')
      ->where('f.fipeCode = :code')
      ->andWhere('f.fuelType = :fuel')
      ->andWhere('f.referenceMonth >= :start')
      ->andWhere('f.referenceMonth <= :end')
      ->setParameter('code', $fipeCode->getCode())
      ->setParameter('fuel', $fuelType->value)
      ->setParameter('start', $startMonth->format())
      ->setParameter('end', $endMonth->format())
      ->orderBy('f.referenceMonth', 'ASC')
      ->getQuery()
      ->getResult();

    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function delete(FipeEntry $fipeEntry): void
  {
    $entity = $this->find($fipeEntry->getId()->toRfc4122());
    if ($entity) {
      $this->getEntityManager()->remove($entity);
      $this->getEntityManager()->flush();
    }
  }

  public function exists(FipeCode $fipeCode, YearMonth $month, FuelType $fuelType): bool
  {
    $count = $this->createQueryBuilder('f')
      ->select('COUNT(f.id)')
      ->where('f.fipeCode = :code')
      ->andWhere('f.referenceMonth = :month')
      ->andWhere('f.fuelType = :fuel')
      ->setParameter('code', $fipeCode->getCode())
      ->setParameter('month', $month->format())
      ->setParameter('fuel', $fuelType->value)
      ->getQuery()
      ->getSingleScalarResult();

    return $count > 0;
  }

  public function findRecentByCategory(string $category, int $limit = 50): array
  {
    $entities = $this->createQueryBuilder('f')
      ->where('f.category = :category')
      ->setParameter('category', $category)
      ->orderBy('f.referenceMonth', 'DESC')
      ->addOrderBy('f.createdAt', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();

    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function getAll(int $page, int $limit, array $filters = []): array
  {
    $qb = $this->createQueryBuilder('f');

    // Apply filters
    if (isset($filters['fipeCode'])) {
      $qb->andWhere('f.fipeCode = :fipeCode')
        ->setParameter('fipeCode', $filters['fipeCode']);
    }

    if (isset($filters['brand'])) {
      $qb->andWhere('f.brand = :brand')
        ->setParameter('brand', $filters['brand']);
    }

    if (isset($filters['model'])) {
      $qb->andWhere('f.model = :model')
        ->setParameter('model', $filters['model']);
    }

    if (isset($filters['referenceMonth'])) {
      $qb->andWhere('f.referenceMonth = :referenceMonth')
        ->setParameter('referenceMonth', $filters['referenceMonth']);
    }

    // Pagination
    $qb->setFirstResult(($page - 1) * $limit)
      ->setMaxResults($limit)
      ->orderBy('f.referenceMonth', 'DESC');

    $entities = $qb->getQuery()->getResult();
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function calculatePriceVariation(
    FipeCode $fipeCode,
    FuelType $fuelType,
    YearMonth $fromMonth,
    YearMonth $toMonth
  ): ?float {
    $fromEntry = $this->findLatestByMonth($fipeCode, $fuelType, $fromMonth);
    $toEntry = $this->findLatestByMonth($fipeCode, $fuelType, $toMonth);

    if (!$fromEntry || !$toEntry) {
      return null;
    }

    $fromPrice = $fromEntry->getPrice()->getValue();
    $toPrice = $toEntry->getPrice()->getValue();

    if ($fromPrice == 0) {
      return null;
    }

    return (($toPrice - $fromPrice) / $fromPrice) * 100;
  }

  public function countAll(array $filters = []): int
  {
    $qb = $this->createQueryBuilder('f')
      ->select('COUNT(f.id)');

    // Apply same filters as getAll
    if (isset($filters['fipeCode'])) {
      $qb->andWhere('f.fipeCode = :fipeCode')
        ->setParameter('fipeCode', $filters['fipeCode']);
    }

    if (isset($filters['brand'])) {
      $qb->andWhere('f.brand = :brand')
        ->setParameter('brand', $filters['brand']);
    }

    if (isset($filters['model'])) {
      $qb->andWhere('f.model = :model')
        ->setParameter('model', $filters['model']);
    }

    if (isset($filters['referenceMonth'])) {
      $qb->andWhere('f.referenceMonth = :referenceMonth')
        ->setParameter('referenceMonth', $filters['referenceMonth']);
    }

    return (int) $qb->getQuery()->getSingleScalarResult();
  }

  private function findLatestByMonth(FipeCode $fipeCode, FuelType $fuelType, YearMonth $month): ?FipeEntry
  {
    $entity = $this->createQueryBuilder('f')
      ->where('f.fipeCode = :code')
      ->andWhere('f.fuelType = :fuel')
      ->andWhere('f.referenceMonth = :month')
      ->setParameter('code', $fipeCode->getCode())
      ->setParameter('fuel', $fuelType->value)
      ->setParameter('month', $month->format())
      ->orderBy('f.createdAt', 'DESC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

    return $entity ? $entity->toDomain() : null;
  }
}
