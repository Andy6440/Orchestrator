<?php
namespace App\Repository;

use App\Entity\Session;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionRepository extends ServiceEntityRepository
{

    private $entityManager;
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
        $this->entityManager = $registry->getManager();
    }

     public function save(Session $session): void
    {
        $this->entityManager->persist($session);
        $this->entityManager->flush();
    }

    /**
     * Limitar el número de sesiones activas por usuario (ejemplo: máximo 3 sesiones)
     */
    public function enforceSessionLimit( $user, int $maxSessions = 3): void
    {
        $sessions = $this->findBy(['user' => $user]);
        $entityManager = $this->getEntityManager();
        if (count($sessions) >= $maxSessions) {
            // Ordenamos por fecha de creación (más antiguas primero)
            usort($sessions, fn($a, $b) => $a->getCreatedAt() <=> $b->getCreatedAt());

            // Eliminamos las más antiguas hasta que queden solo $maxSessions activas
            for ($i = 0; $i < count($sessions) - $maxSessions + 1; $i++) {
                $this->entityManager->remove($sessions[$i]);
            }

            $this->entityManager->flush();
        }
    }
}
