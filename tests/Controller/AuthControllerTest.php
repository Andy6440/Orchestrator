<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AuthControllerTest extends WebTestCase
{
    public function testLogin(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'superadmin@example.com',
                'password' => 'superadmin123',
            ])
        );

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
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
        $client = static::createClient();
        $client->request(
            'POST',
            '/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'wrong@example.com',  // Email incorrecto
                'password' => 'wrongpassword',   // Contraseña incorrecta
            ])
        );

        self::assertResponseStatusCodeSame(401); // Debería devolver 401 Unauthorized
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('success', $data);
        self::assertFalse($data['success']); // El login debe fallar
        self::assertArrayHasKey('message', $data);
        self::assertEquals('Invalid credentials', $data['message']); // Mensaje esperado
    }
}
