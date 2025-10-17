<?php

namespace App\Infra\Persistence\Doctrine\Repository;

use App\Domain\Entity\ValueObjects\VehicleSpecification;
use App\Domain\Entity\Vehicle;
use App\Domain\Repositories\VehicleRepositoryInterface;
use App\Infra\Persistence\Doctrine\Entity\VehicleEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Domain\Entity\ValueObjects\VehicleStatus;
use App\Domain\Entity\ValueObjects\FipeCode;
use App\Domain\Entity\ValueObjects\Price;

class VehicleRepository extends ServiceEntityRepository implements VehicleRepositoryInterface
{
  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, VehicleEntity::class);
  }

  public function save(Vehicle $vehicle): void
  {
    $entity = VehicleEntity::fromDomain($vehicle);
    $this->getEntityManager()->persist($entity);
    $this->getEntityManager()->flush();
  }

  public function update(Vehicle $vehicle): void
  {
    $entity = VehicleEntity::fromDomain($vehicle);
    $entitySaved = $this->getEntityManager()->find(VehicleEntity::class, $entity->getId());
    $entitySaved->update($entity);
    $this->getEntityManager()->persist($entitySaved);
    $this->getEntityManager()->flush();
  }

  public function findById(string $id): ?Vehicle
  {
    $entity = $this->find($id);
    return $entity ? $entity->toDomain() : null;
  }

  public function findByOwnerId(string $ownerId): array
  {
    $entities = $this->findBy(['ownerId' => $ownerId]);
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findByStatus(VehicleStatus $status): array
  {
    $entities = $this->findBy(['status' => $status]);
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findBySpecification(VehicleSpecification $specification): array
  {
    $entities = $this->findBy([
      'brand' => $specification->getBrand(),
      'model' => $specification->getModel(),
      'version' => $specification->getVersion(),
    ]);
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findByFipeCode(FipeCode $fipeCode): array
  {
    $entities = $this->findBy(['fipeCode' => $fipeCode->getCode()]);
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findByPriceRange(Price $minPrice, Price $maxPrice): array
  {
    $qb = $this->createQueryBuilder('v');
    $qb->where('v.priceValue >= :minPrice')
      ->andWhere('v.priceValue <= :maxPrice')
      ->setParameter('minPrice', $minPrice->getValue())
      ->setParameter('maxPrice', $maxPrice->getValue());

    $entities = $qb->getQuery()->getResult();
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findSimilarVehicles(Vehicle $vehicle, int $limit = 10): array
  {
    $spec = $vehicle->getSpecification();
    $qb = $this->createQueryBuilder('v');
    $qb->where('v.brand = :brand')
      ->andWhere('v.model = :model')
      ->setParameter('brand', $spec->getBrand())
      ->setParameter('model', $spec->getModel())
      ->setMaxResults($limit);

    $entities = $qb->getQuery()->getResult();
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function findAvailableVehicles(int $limit = 20, int $offset = 0): array
  {
    $qb = $this->createQueryBuilder('v');
    $qb->where('v.status = :status')
      ->setParameter('status', VehicleStatus::AVAILABLE)
      ->setMaxResults($limit)
      ->setFirstResult($offset);

    $entities = $qb->getQuery()->getResult();
    return array_map(fn($entity) => $entity->toDomain(), $entities);
  }

  public function delete(Vehicle $vehicle): void
  {
    $entity = VehicleEntity::fromDomain($vehicle);
    $this->getEntityManager()->remove($entity);
    $this->getEntityManager()->flush();
  }

  public function exists(string $id): bool
  {
    $entity = $this->find($id);
    return $entity !== null;
  }

  public function countByOwnerId(string $ownerId): int
  {
    $qb = $this->createQueryBuilder('v');
    $qb->select('COUNT(v.id)')
      ->where('v.ownerId = :ownerId')
      ->setParameter('ownerId', $ownerId);

    return (int) $qb->getQuery()->getSingleScalarResult();
  }

  public function countByStatus(VehicleStatus $status): int
  {
    $qb = $this->createQueryBuilder('v');
    $qb->select('COUNT(v.id)')
      ->where('v.status = :status')
      ->setParameter('status', $status);

    return (int) $qb->getQuery()->getSingleScalarResult();
  }
}
