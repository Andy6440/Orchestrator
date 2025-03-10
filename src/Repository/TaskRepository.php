<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Session;
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
    private $eventRepository;
    public function __construct(ManagerRegistry $registry, EventRepository $eventRepository)
    {
        parent::__construct($registry, Task::class);
        $this->entityManager = $registry->getManager();
        $this->eventRepository = $eventRepository;
    }

    public function save(array $taskData): Task
    {
        // Buscar el usuario creador en la base de datos
        $userRepository = $this->entityManager->getRepository(User::class);
        $createdBy = $userRepository->find($taskData['createdBy']);

        if (!$createdBy) {
            throw new ApiException('User not found', ['createdBy' => 'User not found'], 404);
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
        // 1️⃣ Registrar el evento AUTOMÁTICAMENTE en el `save()`
        $this->eventRepository->create($task, 'task_created', [
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'assignedTo' => $task->getAssignedTo() ? $task->getAssignedTo()->getId() : null,
            'createdBy' => $task->getCreatedBy()->getId(),
        ]);



        return $task;
    }

    public function update($task, array $taskData, Session $session): Task
    {
        $task = $this->findActiveTask($task);

        if (!$task) {
            throw new ApiException('Task not found', ['taskId' => 'Task not found'], 404);
        }
        // 3️⃣ Obtener valores antes de la actualización para el evento
        $previousData = [
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'assignedTo' => $task->getAssignedTo() ? $task->getAssignedTo()->getId() : null
        ];
        // Buscar el usuario asignado si se proporciona
        if (isset($taskData['assignedTo'])) {
            $userRepository = $this->entityManager->getRepository(User::class);
            $assignedTo = $userRepository->find($taskData['assignedTo']);

            if (!$assignedTo) {
                throw new ApiException('Assigned user not found', ['assignedTo' => 'User not found'], 404);
            }

            $task->setAssignedTo($assignedTo);
        }

        // Actualizar los datos de la tarea
        if (isset($taskData['title'])) {
            $task->setTitle($taskData['title']);
        }

        if (isset($taskData['description'])) {
            $task->setDescription($taskData['description']);
        }

        if (isset($taskData['status'])) {
            $task->setStatus($taskData['status']);
        }


        // Guardar los cambios en la base de datos
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        $this->eventRepository->create($task, 'task_updated', [
            'previous' => $previousData,
            'new' => [
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'assignedTo' => $task->getAssignedTo() ? $task->getAssignedTo()->getId() : null
            ],
            'updatedBy' => $session->getUser()->getId(), // 🔹 SOLO en el evento
            'updatedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s')
        ]);

        return $task;
    }

    public function delete($taskId, $session)
    {
        // 1️⃣ Buscar la tarea
        $task = $this->findActiveTask($taskId);
        if (!$task) {
            throw new ApiException('Task not found', ['task' => 'Task not found'], 404);
        }

        // 2️⃣ Verificar si ya está eliminada
        if ($task->isDeleted()) {
            throw new ApiException('Task already deleted', ['task' => 'This task has already been deleted'], 400);
        }

        // 3️⃣ Marcar la tarea como eliminada
        $task->setDeletedAt(new \DateTimeImmutable());
        $task->setStatus('deleted');
        
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // 4️⃣ Registrar el evento de eliminación
        $this->eventRepository->create($task, 'task_deleted', [
            'deletedBy' => $session->getUser()->getId(),
            'deletedAt' => $task->getDeletedAt()->format('Y-m-d H:i:s')
        ]);

        return ['success' => true, 'message' => 'Task deleted successfully'];
    }

    public function findActiveTask($taskId): ?Task
    {
        return $this->createQueryBuilder('t')
            ->where('t.id = :id')
            ->andWhere('t.deletedAt IS NULL') // 🔹 Excluir tareas eliminadas
            ->setParameter('id', $taskId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
