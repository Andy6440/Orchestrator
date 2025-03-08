<?php

namespace App\DataFixtures;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Recuperar el rol desde `RoleFixtures`
        $roleSuperAdmin = $this->getReference('ROLE_SUPER_ADMIN',Role::class);

        // Crear usuario Super Admin
        $superAdmin = new User();
        $superAdmin->setEmail('superadmin@example.com');
        $superAdmin->setName('Super Admin');
        $superAdmin->setLastName('Super Admin');
        $superAdmin->setPassword(
            $this->passwordHasher->hashPassword($superAdmin, 'superadmin123')
        );
        $superAdmin->addRole($roleSuperAdmin);

        $manager->persist($superAdmin);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            RoleFixtures::class, // Asegura que los roles se cargan antes de los usuarios
        ];
    }
}
