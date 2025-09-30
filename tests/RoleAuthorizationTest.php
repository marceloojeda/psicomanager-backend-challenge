<?php

namespace Tests;

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseTransactions;

class RoleAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_role_cannot_access_users_endpoint()
    {
        // Autenticar como usuário normal (não admin)
        $this->authenticateUser();
        
        $response = $this->authenticatedGet('/users');

        $this->assertEquals(403, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Acesso negado', $responseData['error']);
    }

    public function test_admin_role_can_access_users_endpoint()
    {
        // Autenticar como admin
        $this->authenticateAdmin();
        
        $response = $this->authenticatedGet('/users');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function test_user_can_see_own_tasks_only()
    {
        // Criar usuário de teste
        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'user' . time() . '@test.com',
            'password' => app('hash')->make('password123')
        ]);
        
        // Criar tarefas para este usuário
        $userTasks = \App\Models\Task::factory()->count(3)->create(['user_id' => $user->id]);
        
        // Criar outro usuário e suas tarefas
        $otherUser = User::factory()->create([
            'role' => 'user',
            'email' => 'other' . time() . '@test.com',
            'password' => app('hash')->make('password123')
        ]);
        $otherUserTasks = \App\Models\Task::factory()->count(2)->create(['user_id' => $otherUser->id]);

        // Autenticar como o primeiro usuário
        $loginResponse = $this->json('POST', '/auth/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);
        
        $this->assertEquals(200, $loginResponse->response->getStatusCode());
        
        $loginData = json_decode($loginResponse->response->getContent(), true);
        $this->assertArrayHasKey('access_token', $loginData);
        
        $this->authToken = $loginData['access_token'];
        
        // Fazer requisição para ver tarefas
        $response = $this->authenticatedGet('/tasks');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('data', $responseData);
        
        $tasks = $responseData['data'];
        
        // Todas as tarefas devem ser do usuário logado
        foreach ($tasks as $task) {
            $this->assertEquals($user->id, $task['user_id']);
        }
        
        // Deve conter as 3 tarefas criadas para este usuário
        $userTaskIds = array_column($userTasks->toArray(), 'id');
        $responseTaskIds = array_column($tasks, 'id');
        
        foreach ($userTaskIds as $taskId) {
            $this->assertContains($taskId, $responseTaskIds, "Tarefa {$taskId} deveria estar na resposta");
        }
    }

    public function test_admin_can_see_all_tasks()
    {
        // Criar usuários
        $user1 = User::factory()->create([
            'role' => 'user',
            'email' => 'user1' . time() . '@test.com'
        ]);
        $user2 = User::factory()->create([
            'role' => 'user', 
            'email' => 'user2' . time() . '@test.com'
        ]);
        
        // Criar tarefas para ambos usuários
        $user1Tasks = \App\Models\Task::factory()->count(2)->create(['user_id' => $user1->id]);
        $user2Tasks = \App\Models\Task::factory()->count(3)->create(['user_id' => $user2->id]);

        // Autenticar como admin
        $this->authenticateAdmin();
        
        // Fazer requisição para ver tarefas
        $response = $this->authenticatedGet('/tasks');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('data', $responseData);
        
        $tasks = $responseData['data'];
        
        // Verificar se as tarefas criadas estão na resposta
        $user1TaskIds = array_column($user1Tasks->toArray(), 'id');
        $user2TaskIds = array_column($user2Tasks->toArray(), 'id');
        $responseTaskIds = array_column($tasks, 'id');
        
        $foundUser1Tasks = 0;
        $foundUser2Tasks = 0;
        
        foreach ($user1TaskIds as $taskId) {
            if (in_array($taskId, $responseTaskIds)) {
                $foundUser1Tasks++;
            }
        }
        
        foreach ($user2TaskIds as $taskId) {
            if (in_array($taskId, $responseTaskIds)) {
                $foundUser2Tasks++;
            }
        }
        
        $this->assertEquals(2, $foundUser1Tasks, 'Admin deve ver todas as 2 tarefas do usuário 1');
        $this->assertEquals(3, $foundUser2Tasks, 'Admin deve ver todas as 3 tarefas do usuário 2');
    }

    public function test_user_role_field_is_returned_in_auth()
    {
        // Criar usuário com role específica
        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'roletest' . time() . '@test.com',
            'password' => app('hash')->make('password123')
        ]);
        
        // Login
        $response = $this->json('POST', '/auth/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);
        
        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('role', $responseData['user']);
        $this->assertEquals('user', $responseData['user']['role']);
    }

    public function test_admin_role_field_is_returned_in_auth()
    {
        // Criar admin
        $admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admintest' . time() . '@test.com',
            'password' => app('hash')->make('password123')
        ]);
        
        // Login
        $response = $this->json('POST', '/auth/login', [
            'email' => $admin->email,
            'password' => 'password123'
        ]);
        
        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('role', $responseData['user']);
        $this->assertEquals('admin', $responseData['user']['role']);
    }
}