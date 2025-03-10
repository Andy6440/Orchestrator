<?php

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use App\Exception\ApiException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    private $entityManager;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
        $this->entityManager = $registry->getManager();
    }

    public function save(array $taskData): Task
    {
        // Buscar el usuario creador en la base de datos
        $userRepository = $this->entityManager->getRepository(User::class);
        $createdBy = $userRepository->find($taskData['createdBy']);

        if (!$createdBy) {
            throw new ApiException('User not found',['createdBy' => 'User not found'], 404);
        }

        // Buscar el usuario asignado si existe
        $assignedTo = null;
        if (!empty($taskData['assignedTo'])) {
            $assignedTo = $userRepository->find($taskData['assignedTo']);
            if (!$assignedTo) {
                throw new ApiException('Assigned user not found', ['assignedTo' => 'User not found'], 404);
            }
        }

        // Crear nueva tarea y asignar los datos
        $task = new Task();
        $task->setTitle($taskData['title']);
        $task->setDescription($taskData['description'] ?? null);
        $task->setStatus($taskData['status']);
        $task->setAssignedTo($assignedTo);
        $task->setCreatedBy($createdBy);

        // Guardar la tarea en la base de datos
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }
}
