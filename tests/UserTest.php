<?php

namespace Tests;
use \App\Models\User;

class UserTest extends TestCase
{
    /**
     * Testar a lista de usuarios na rota /users
     */
    public function test_that_endpoint_users_returns_a_successful_response(): void
    {
        $this->get("users", []);
        $this->seeStatusCode(200);
        $this->seeJsonStructure([
            'message',
            'data' => [
                '*' =>
                    [
                        'id',
                        'name',
                        'email',
                        'created_at'
                    ]
                ],
            'error',
            'status'
        ]);
    }

    /**
     * Testar a rota de pegar usuario na rota /users/userId
     */
    public function test_that_endpoint_get_a_user_returns_a_successful_response(): void
    {
        $user = User::first();

        $response = $this->get("users/{$user->id}");

        $response->seeStatusCode(200);

        $response->seeJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'created_at'
            ],
            'error',
            'status'
        ]);
    }

    /**
     * Testar a rota de cadastrar usuario na rota /users
     */
    public function test_that_endpoint_create_a_user_returns_a_successful_response(): void
    {
        $response = $this->post('users', [
            'name' => 'John Doe',
            'email' => 'johndoe22@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678'
        ]);

        $response->seeStatusCode(200);

        $response->seeJsonStructure([
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'created_at'
            ],
            'error',
            'status'
        ]);
    }
}
