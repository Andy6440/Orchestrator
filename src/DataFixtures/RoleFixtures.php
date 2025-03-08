<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        //define roles
        $roles = [
            'ROLE_SUPER_ADMIN' => 'Super Administrator',
            'ROLE_ADMIN' => 'Administrator',
            'ROLE_MANAGER' => 'Manager',
            'ROLE_USER' => 'User',
            'ROLE_AI_ASSISTANT' => 'AI Assistant'
        ];

        $roleEntities = [];

        foreach ($roles as $code => $name) {
            $role = new Role();
            $role->setCode($code);
            $role->setName($name);
            $manager->persist($role);
            $roleEntities[$code] = $role;
            $this->addReference($code, $role);
        }

        $manager->flush();
    }
}
