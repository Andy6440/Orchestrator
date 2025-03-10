<?php

namespace App\Tests\Controller;

use App\Entity\Role;
use App\Entity\Session;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AuthControllerTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private $client;
    protected function setUp(): void
    {
        $this->client = static::createClient(); // ✅ Esto inicia el kernel correctamente
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }


    public function testLogin(): void
    {
        $this->client->request(
            'POST',
            'auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'superadmin@example.com',
                'password' => 'superadmin123',
            ])
        );

        self::assertResponseIsSuccessful();
        self::assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('success', $data);
        self::assertTrue($data['success']);
        self::assertArrayHasKey('message', $data);
        self::assertEquals('Login successful', $data['message']);
        self::assertArrayHasKey('data', $data);
        self::assertArrayHasKey('token', $data['data']);
        self::assertArrayHasKey('expires_at', $data['data']);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->client->request(
            'POST',
            'auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'wrong@example.com',  // Email incorrecto
                'password' => 'wrongpassword',   // Contraseña incorrecta
            ])
        );

        self::assertResponseStatusCodeSame(401); // Debería devolver 401 Unauthorized
        self::assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('success', $data);
        self::assertFalse($data['success']); // El login debe fallar
        self::assertArrayHasKey('message', $data);
        self::assertEquals('Invalid credentials', $data['message']); // Mensaje esperado
    }

    public function testUserLogout()
    {
        $entityManager = $this->entityManager;

        // 1️⃣ Buscar o crear el usuario de prueba
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'testuser@example.com']);
        if (!$user) {
            $roleUser = $entityManager->getRepository(Role::class)->findOneBy(['code' => 'ROLE_USER']);
            // Crear usuario de prueba
            $testUser = new User();
            $testUser->setEmail('testuser@example.com');
            $testUser->setName('Test');
            $testUser->setLastName('User');
            $testUser->setPassword(hash('sha256', 'testuser123'));
            $testUser->addRole($roleUser);
            $entityManager->persist($testUser);
            $entityManager->flush();
            $user = $testUser;
        }

        // 2️⃣ Crear una sesión asociada al usuario
        $session = new Session($user);
        $entityManager->persist($session);
        $entityManager->flush();

        // 3️⃣ Llamar al endpoint de logout con la sesión
        $this->client->request('POST', 'api/auth/logout', [], [], [
            'HTTP_Authorization' => 'Bearer ' . $session->getSessionId()
        ]);

        // 4️⃣ Verificar la respuesta
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(["success" => true, "message" => "Logout successful", "data" => ["logout" => true]]),
            $response->getContent()
        );

        // 5️⃣ Verificar que la sesión fue eliminada
        $deletedSession = $entityManager->getRepository(Session::class)->findOneBy(['sessionId' => $session->getSessionId()]);
        $this->assertNull($deletedSession, 'Session should be deleted after logout');
    }

    public function testLogoutWithInvalidSession()
    {
        // 1️⃣ Llamar al endpoint de logout con un token inválido
        $this->client->request('POST', 'api/auth/logout', [], [], [
            'HTTP_Authorization' => 'Bearer invalid-session-token'
        ]);

        // 2️⃣ Verificar la respuesta esperada
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(["success" => false, "message" => "Invalid session", "errors" => []]),
            $response->getContent()
        );
    }

    public function testUserRegister()
    {
        $entityManager = $this->entityManager;

        // 1️⃣ Generar un email aleatorio para evitar conflictos en los tests
        $randomEmail = 'testuser_' . uniqid() . '@example.com';

        // 2️⃣ Datos del usuario de prueba
        $userData = [
            "email" => $randomEmail,
            "name" => "Test",
            "lastName" => "User",
            "password" => "TestUser123",
            "roles" => [
                "ROLE_USER"
            ]
        ];

        // 3️⃣ Llamar al endpoint de registro
        $this->client->request('POST', '/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($userData));

        // 4️⃣ Verificar la respuesta de la API
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode(), "Response status should be 201 Created");
        self::assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('success', $data);

        // 5️⃣ Verificar que el usuario fue guardado en la base de datos
        $newUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $randomEmail]);
        $this->assertNotNull($newUser, 'User should be saved in the database');
    }

    public function testUserRegisterFailsDueToInvalidData()
    {
        $entityManager = $this->entityManager;

        // 1️⃣ Datos del usuario de prueba (sin email)
        $userData = [
            "name" => "Test",
            "lastName" => "User",
            "password" => "TestUser123",
            "roles" => [
                "ROLE_USER"
            ]
        ];

        // 2️⃣ Llamar al endpoint de registro con datos inválidos
        $this->client->request('POST', '/auth/register', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode($userData));

        // 3️⃣ Verificar la respuesta de la API (debe fallar con 400 Bad Request)
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), "Response should be 400 Bad Request");
        self::assertJson($response->getContent());

        // 4️⃣ Verificar que el JSON contiene los errores esperados
        $data = json_decode($response->getContent(), true);
        self::assertArrayHasKey('message', $data);
        self::assertEquals('Invalid data', $data['message']);
        self::assertArrayHasKey('errors', $data);
        self::assertArrayHasKey('email', $data['errors']); // Debe mostrar error por falta de email
    }


    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client = null;
        $this->entityManager->close();
    }
}
