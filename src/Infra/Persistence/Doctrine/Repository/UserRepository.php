<?php

namespace App\Infra\Persistence\Doctrine\Repository;

use App\Domain\Entity\User as DomainUser;
use App\Infra\Persistence\Doctrine\Entity\User;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Infra\Persistence\Doctrine\Entity\UserEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserEntity::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof UserEntity) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function save(DomainUser $user): void
    {
        $entity = UserEntity::fromDomain($user);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?DomainUser
    {
        $entity = $this->find($id);
        return $entity ? $entity->toDomain() : null;
    }

    public function findByEmail(string $email): ?DomainUser
    {
        $entity = $this->findOneBy(['email' => $email]);
        return $entity ? $entity->toDomain() : null;
    }

    public function delete(DomainUser $user): void
    {
        $entity = UserEntity::fromDomain($user);
        $this->getEntityManager()->remove($entity);
        $this->getEntityManager()->flush();
    }

    public function existsByEmail(string $email): bool
    {
        $entity = $this->findOneBy(['email' => $email]);
        return $entity !== null;
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
