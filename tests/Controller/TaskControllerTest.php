<?php

namespace App\Tests\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Entity\Session;
use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;

class TaskControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $bearerToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // 1️⃣ Buscar o crear el usuario de prueba
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'testuser@example.com']);
        if (!$user) {
            $roleUser = $this->entityManager->getRepository(Role::class)->findOneBy(['code' => 'ROLE_USER']);
            if (!$roleUser) {
                $roleUser = new Role();
                $roleUser->setCode('ROLE_USER');
                $this->entityManager->persist($roleUser);
                $this->entityManager->flush();
            }

            $testUser = new User();
            $testUser->setEmail('testuser@example.com');
            $testUser->setName('Test');
            $testUser->setLastName('User');
            $testUser->setPassword(hash('sha256', 'testuser123')); // Simulando hash de password
            $testUser->addRole($roleUser);

            $this->entityManager->persist($testUser);
            $this->entityManager->flush();
            $user = $testUser;
        }

        // 2️⃣ Crear una sesión asociada al usuario
        $session = new Session($user);
        $this->entityManager->persist($session);
        $this->entityManager->flush();
        // 3️⃣ Obtener el token de sesión para enviarlo en el Bearer Token
        $this->bearerToken = $session->getSessionId();
    }

    public function testCreateTaskSuccessfully(): void
    {
        $taskData = [
            "title" => "Revisión de código",
            "description" => "Revisar el PR #123 y sugerir mejoras.",
            "status" => "pending",
            "assignedTo" => 2,
        ];

        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->bearerToken, // Enviamos el token en el header
            ],
            json_encode($taskData)
        );

        $response = $this->client->getResponse();
        // Verifica que la respuesta es 201 (Created)
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());

        // Decodifica la respuesta JSON
        $responseData = json_decode($response->getContent(), true);
        // Verifica que la estructura de la respuesta es la esperada
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Registration successful', $responseData['message']);
        $this->assertArrayHasKey('task', $responseData['data']);

        // 5️⃣ Verificar que la sesión fue eliminada
        $deletedSession = $this->entityManager->getRepository(Task::class)->findOneBy(['id' => $responseData['data']['task']['id']]);
        $this->assertNotNull($deletedSession, 'Task should be created successfully');
    }

    public function testCreateTaskFailsWithInvalidData(): void
    {
        $taskData = [
            "title" => "", // ❌ Título vacío (inválido)
            "description" => "Revisar el PR #123 y sugerir mejoras.",
            "status" => "invalid_status", // ❌ Estado inválido
            "assignedTo" => 9999, // ❌ Usuario inexistente
        ];

        $this->client->request(
            'POST',
            '/api/tasks',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->bearerToken, // Enviamos el token en el header
            ],
            json_encode($taskData)
        );

        $response = $this->client->getResponse();

        // 1️⃣ Verifica que la respuesta es 400 (Bad Request)
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        // 2️⃣ Decodifica la respuesta JSON
        $responseData = json_decode($response->getContent(), true);

        // 3️⃣ Verifica que `success` es `false`
        $this->assertFalse($responseData['success']);

        // 4️⃣ Verifica que el mensaje de error es correcto
        $this->assertEquals('Invalid data', $responseData['message']);

        // 5️⃣ Verifica que se reciben errores específicos
        $this->assertArrayHasKey('errors', $responseData);

        $errors = $responseData['errors'];

        $this->assertArrayHasKey('title', $errors);
        $this->assertEquals('The title is required and must have a maximum of 255 characters.', $errors['title']);

        $this->assertArrayHasKey('status', $errors);
        $this->assertEquals('The status must be one of: pending, in_progress, completed.', $errors['status']);

        $this->assertArrayHasKey('assignedTo', $errors);
        $this->assertEquals('The specified assigned user does not exist.', $errors['assignedTo']);

        // 6️⃣ Verifica que la tarea NO fue creada en la base de datos
        $createdTask = $this->entityManager->getRepository(Task::class)->findOneBy(['title' => '']);
        $this->assertNull($createdTask, 'Task should not be created when data is invalid');
    }

    public function testUpdateTaskSuccessfully(): void
    {
        // 1️⃣ Crear una tarea de prueba en la base de datos
        $task = new Task();
        $task->setTitle("Tarea Inicial");
        $task->setDescription("Descripción inicial.");
        $task->setStatus("pending");

        // Buscar usuario asignado y creador (suponiendo que existen)
        $assignedTo = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'testuser@example.com']);
        $createdBy = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'superadmin@example.com']);

        $task->setAssignedTo($assignedTo);
        $reflection = new \ReflectionClass(Task::class);
        $property = $reflection->getProperty('createdBy');
        $property->setAccessible(true);
        $property->setValue($task, $createdBy);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // 2️⃣ Datos actualizados
        $updatedData = [
            "title" => "Tarea Actualizada",
            "description" => "Descripción actualizada.",
            "status" => "in_progress",
            "assignedTo" => $assignedTo->getId(),
        ];

        // 3️⃣ Enviar solicitud PUT con el Bearer Token
        $this->client->request(
            'POST',
            '/api/tasks/' . $task->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->bearerToken,
            ],
            json_encode($updatedData)
        );

        $response = $this->client->getResponse();

        // 4️⃣ Verificar que la respuesta es 200 (OK)
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // 5️⃣ Decodificar la respuesta JSON
        $responseData = json_decode($response->getContent(), true);

        // 6️⃣ Verificar que `success` es `true` y `message` es "Update successful"
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Update successful', $responseData['message']);

        // 7️⃣ Verificar que la tarea fue actualizada correctamente en la base de datos
        $updatedTask = $this->entityManager->getRepository(Task::class)->find($task->getId());

        $this->assertEquals("Tarea Actualizada", $updatedTask->getTitle());
        $this->assertEquals("Descripción actualizada.", $updatedTask->getDescription());
        $this->assertEquals("in_progress", $updatedTask->getStatus());
        $this->assertEquals($assignedTo->getId(), $updatedTask->getAssignedTo()->getId());
    }

    public function testUpdateTaskFailsWithInvalidData(): void
    {
        // 1️⃣ Crear una tarea de prueba en la base de datos
        $task = new Task();
        $task->setTitle("Tarea Original");
        $task->setDescription("Descripción original.");
        $task->setStatus("pending");

        // Buscar usuario asignado y creador (suponiendo que existen)
        $assignedTo = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'testuser@example.com']);
        $createdBy = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'superadmin@example.com']);

        $task->setAssignedTo($assignedTo);
        $reflection = new \ReflectionClass(Task::class);
        $property = $reflection->getProperty('createdBy');
        $property->setAccessible(true);
        $property->setValue($task, $createdBy);

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // 2️⃣ Datos inválidos para la actualización
        $invalidData = [
            "title" => "", // ❌ Título vacío
            "description" => "Nueva descripción.",
            "status" => "invalid_status", // ❌ Estado inválido
            "assignedTo" => 9999, // ❌ Usuario inexistente
        ];

        // 3️⃣ Enviar solicitud PUT con datos inválidos
        $this->client->request(
            'POST',
            '/api/tasks/' . $task->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->bearerToken,
            ],
            json_encode($invalidData)
        );

        $response = $this->client->getResponse();

        // 4️⃣ Verificar que la respuesta es 400 (Bad Request)
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        // 5️⃣ Decodificar la respuesta JSON
        $responseData = json_decode($response->getContent(), true);

        // 6️⃣ Verificar que `success` es `false` y `message` es "Invalid data"
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Invalid data', $responseData['message']);

        // 7️⃣ Verificar que se reciben errores específicos
        $this->assertArrayHasKey('errors', $responseData);

        $errors = $responseData['errors'];

        $this->assertArrayHasKey('title', $errors);
        $this->assertEquals('The title is required and must have a maximum of 255 characters.', $errors['title']);

        $this->assertArrayHasKey('status', $errors);
        $this->assertEquals('The status must be one of: pending, in_progress, completed.', $errors['status']);

        $this->assertArrayHasKey('assignedTo', $errors);
        $this->assertEquals('The specified assigned user does not exist.', $errors['assignedTo']);

        // 8️⃣ Verificar que la tarea en la base de datos NO se haya actualizado
        $unchangedTask = $this->entityManager->getRepository(Task::class)->find($task->getId());

        $this->assertEquals("Tarea Original", $unchangedTask->getTitle());
        $this->assertEquals("Descripción original.", $unchangedTask->getDescription());
        $this->assertEquals("pending", $unchangedTask->getStatus());
    }

    public function testDeleteTaskSuccessfully(): void
    {
        // 1️⃣ Crear una tarea de prueba en la base de datos
        $task = new Task();
        $task->setTitle("Tarea a Eliminar");
        $task->setDescription("Esta tarea será eliminada.");
        $task->setStatus("pending");

        // Buscar usuario asignado y creador (suponiendo que existen)
        $assignedTo = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'testuser@example.com']);
        $createdBy = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'superadmin@example.com']);

        $task->setAssignedTo($assignedTo);
        $task->setCreatedBy($createdBy);
        $this->entityManager->persist($task);
        $this->entityManager->flush();

        // 2️⃣ Enviar solicitud DELETE con el Bearer Token
        $this->client->request(
            'DELETE',
            '/api/tasks/' . $task->getId(),
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->bearerToken,
            ]
        );

        $response = $this->client->getResponse();
        // 3️⃣ Verificar que la respuesta es 200 (OK)
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());

        // 4️⃣ Decodificar la respuesta JSON
        $responseData = json_decode($response->getContent(), true);

        // 5️⃣ Verificar que `success` es `true` y `message` es "Delete successful"
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Delete successful', $responseData['message']);
    }

    public function testDeleteTaskFailsForNonExistentTask(): void
    {
        // 1️⃣ ID de una tarea que no existe
        $nonExistentTaskId = 99999;

        // 2️⃣ Enviar solicitud DELETE con el Bearer Token
        $this->client->request(
            'DELETE',
            '/api/tasks/' . $nonExistentTaskId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->bearerToken,
            ]
        );

        $response = $this->client->getResponse();

        // 3️⃣ Verificar que la respuesta es 404 (Not Found)
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        // 4️⃣ Decodificar la respuesta JSON
        $responseData = json_decode($response->getContent(), true);

        // 5️⃣ Verificar que `success` es `false` y `message` indica error
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Task not found', $responseData['message']);
    }
}
