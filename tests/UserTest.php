<?php

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_users_get_with_filters()
    {
        // Cria usuario para testar o filtro em seguida
        $userCreateData = [
            'name' => 'Testing New User',
            'email' => 'test@new-user.com',
            'password' => 'swordpass'
        ];
        $this->post('/users', $userCreateData);
        $this->assertResponseOk();
        $this->seeJson(['name' => 'Testing New User']);
        $userData = $this->response->json();

        $this->call('GET', '/users', ['name' => 'Testing New User']);
        $this->assertResponseOk();
        $this->seeJson(['name' => 'Testing New User']);
        $this->assertGreaterThan(0, count($this->response->json()));

        $this->call('GET', '/users', ['id' => $userData['id']]);
        $this->assertResponseOk();
        $this->seeJson(['name' => 'Testing New User']);
        $this->seeJson(['id' => $userData['id']]);

    }

    public function test_users_get_prevent_password_output()
    {
        $this->get('/users');

        $this->seeJsonStructure([
            '*' => [
                'name',
                'email'
            ]
        ]);

        collect($this->response->json())
            ->each(fn ($i) =>
                $this->assertArrayNotHasKey('password', $i)
            );
    }

    public function test_users_find_by_id()
    {
        $this->get('/users/30');
        $this->seeJson(['id' => 30]);
    }
}
