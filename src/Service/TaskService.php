<?php

namespace App\Service;

use App\Entity\Task;
use App\Exception\ApiException;
use App\Repository\TaskRepository;
use App\Validator\CreateUserRequest;
use App\Validator\task\CreateTaskRequest;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskService
{
    private ValidatorInterface $validator;
    private TaskRepository $taskRepository;
    private $serializer;
    public function __construct(
        ValidatorInterface $validator,
         SerializerInterface $serializer,
         TaskRepository $taskRepository)
    {
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->taskRepository = $taskRepository;
    }

    public function create(array $data, $session)
    {
        // validate the request data
        $data['createdBy'] = $session->getUser()->getId();
        $this->validator->validate($data, new CreateTaskRequest());

        $task =  $this->taskRepository->save($data);
        // create the task
        $arrayTask = $this->serializer->serialize($task, 'json', ['groups' => ['task:read', 'user:read']]);
        $arrayTask = json_decode($arrayTask, true);
        return ['task' => $arrayTask];

    }

    public function update( $task, array $data, $session)
    {
        $task = $this->taskRepository->find($task);
        if(!$task){
            throw new ApiException('Task not found', ['task' => 'Task not found'], 404);
        }
        // validate the request data
        $data['createdBy'] = $session->getUser()->getId();
        $this->validator->validate($data, new CreateTaskRequest());

        $task =  $this->taskRepository->update($task, $data);
        // create the task
        $arrayTask = $this->serializer->serialize($task, 'json', ['groups' => ['task:read', 'user:read']]);
        $arrayTask = json_decode($arrayTask, true);
        return ['task' => $arrayTask];

    }

}
