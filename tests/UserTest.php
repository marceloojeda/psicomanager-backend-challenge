<?php

namespace Tests;

use App\Models\Task;
use App\Models\User;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserTest extends TestCase
{
    use DatabaseTransactions;
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

        collect($this->response->json())->take(2)
            ->each(function ($i) {
                $this->assertArrayHasKey('name', $i);
                $this->assertArrayHasKey('email', $i);
            });

        collect($this->response->json())->take(2)
            ->each(fn ($i) =>
                $this->assertArrayNotHasKey('password', $i)
            );
    }

    public function test_users_find_by_id()
    {
        $this->get('/users/30');
        $this->seeJson(['id' => 30]);
    }

    public function test_users_store_encrypted_pass()
    {
        $userCreateData = [
            'name' => 'New User With Encrypted Pass',
            'email' => 'encryptedpassuser@test.com',
            'password' => 'clear-text-pass',
        ];
        $this->post('/users', $userCreateData);
        $userId = $this->response->json()['id'];

        $userAssert = User::find($userId);
        $this->assertNotEquals($userAssert->password, 'clear-text-password');
        $this->assertTrue(strlen($userAssert->password) > 40);
    }

    public function test_users_store_validations()
    {
        $userCreateData = [
            'name' => 'sm',
            'email' => 'emailinvalid'
        ];
        $this->post('/users', $userCreateData);
        $this->assertResponseStatus(422);

        $userCreateData = [
            'name' => 'Some Valid User',
            'email' => 'email@validuseremail.com',
            'password' => 'abc123',
        ];
        $this->post('/users', $userCreateData);
        $this->assertResponseStatus(200);

        $this->post('/users', $userCreateData);
        $this->assertResponseStatus(422);
        $this->seeJson(['email' => ['The email has already been taken.']]);
    }

    public function test_users_delete()
    {
        $lastUser = User::orderByDesc('id')->first();
        $this->delete('/users/' . $lastUser->id);
        $this->assertResponseStatus(202);

        $this->delete('/users/' . $lastUser->id);
        $this->assertResponseStatus(404);

        $this->assertTrue(Task::where('user_id', $lastUser->id)->count() === 0);
    }
}
