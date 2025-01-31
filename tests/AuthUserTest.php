<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthUserTest extends TestCase
{
    use DatabaseMigrations;

    public function test_user_authentication()
    {
        $user = \App\Models\User::factory()->create([
            'password' => app('hash')->make('123456abc')
        ]);

        $this->post('/auth/login', [
            'email' => $user->email,
            'password' => '123456abc'
        ])
        ->seeStatusCode(200)
        ->seeJsonStructure([
            'token',
        ]);
    }
}