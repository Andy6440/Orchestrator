<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


    public function findOneByField(string $item, mixed $value, bool $asArray = false): User|array|null
    {
        $queryBuilder = $this->createQueryBuilder('u')
            ->andWhere("u.{$item} = :val")
            ->setParameter('val', $value);

        $query = $queryBuilder->getQuery();

        return $asArray
            ? $query->getOneOrNullResult(Query::HYDRATE_ARRAY) // Devuelve array si $asArray es true
            : $query->getOneOrNullResult(); // Devuelve objeto User si $asArray es false
    }
}
