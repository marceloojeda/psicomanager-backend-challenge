<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseMigrations;

    public function test_get_users()
    {
        $user = \App\Models\User::factory()->create();

        $this->get('/users')
             ->seeStatusCode(200)
             ->seeJson([
                 'id' => $user->id,
                 'name' => $user->name
             ]);
    }

    public function test_get_users_with_name_filter()
    {
        $user = \App\Models\User::factory()->create();
    
        $this->get('/users?name=' . $user->name)
             ->seeStatusCode(200)
             ->seeJson([
                 'name' => $user->name,
             ]);
    }

    public function test_get_users_with_id_filter()
    {
        $user = \App\Models\User::factory()->create();
    
        $this->get('/users?id=' . $user->id)
             ->seeStatusCode(200)
             ->seeJson([
                 'id' => $user->id,
                 'name' => $user->name,
             ]);
    }

    public function test_user_store()
    {
        $user = \App\Models\User::factory()->make();

        $this->post('/users', $user->toArray())
             ->seeStatusCode(201)
             ->seeJson([
                 'name' => $user->name,
                 'email' => $user->email
             ]);
    }

    public function teste_user_delete(){
        $user = \App\Models\User::factory()->create();

        $this->delete("users/{$user->id}")
             ->seeStatusCode(200)
             ->seeJson([
                 'message' => 'Usuário removido com sucesso'
             ]);
    }
}