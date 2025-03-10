<?php

namespace App\Repository;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    private $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
        $this->entityManager = $registry->getManager();
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

    public function saveUserWithRole(array $userData): User 
    {
        // Buscar el rol en la base de datos
        $roleRepository = $this->entityManager->getRepository(Role::class);
        $role = $roleRepository->findBy(['code' => $userData['roles']]);
        if (!$role) {
            throw new \Exception('Role not found');
        }

        // Crear una nueva instancia de User y asignar los datos
        $user = new User();
        $user->setEmail($userData['email']);
        $user->setName($userData['name']);
        $user->setLastName($userData['lastName']);
        $user->setPassword($userData['password']);
        
        foreach ($role as $r) {
            $user->addRole($r);
        }

        // Guardar el usuario en la base de datos
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
