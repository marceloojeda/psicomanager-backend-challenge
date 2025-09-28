<?php

use Tests\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TaskControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function test_tasks_index_with_valid_user_id()
    {
        // Cria usuário específico para o teste
        $user = User::factory()->create([
            'email' => 'task-test-' . time() . '@example.com',
            'password' => app('hash')->make('password123')
        ]);
        
        // Autentica como este usuário específico
        $loginResponse = $this->json('POST', '/auth/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);
        
        $this->assertEquals(200, $loginResponse->response->getStatusCode());
        
        $loginData = json_decode($loginResponse->response->getContent(), true);
        $this->authToken = $loginData['access_token'];
        
        // Cria algumas tarefas para este usuário específico
        Task::create(['user_id' => $user->id, 'description' => 'Task 1', 'status' => 'pendente']);
        Task::create(['user_id' => $user->id, 'description' => 'Task 2', 'status' => 'em progresso']);

        // Usuário vê suas próprias tarefas
        $response = $this->authenticatedGet("/tasks");

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('current_page', $responseData);
        $this->assertArrayHasKey('per_page', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        
        foreach ($responseData['data'] as $task) {
            $this->assertArrayHasKey('id', $task);
            $this->assertArrayHasKey('user_id', $task);
            $this->assertArrayHasKey('description', $task);
            $this->assertArrayHasKey('status', $task);
            $this->assertArrayHasKey('created_at', $task);
            $this->assertArrayHasKey('updated_at', $task);
        }

        $this->assertEquals(2, count($responseData['data']));
        $this->assertEquals(2, $responseData['total']);
    }

    /** @test */
    public function test_tasks_index_with_pagination()
    {
        // Cria usuário de teste
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        // Cria 10 tarefas
        for ($i = 1; $i <= 10; $i++) {
            Task::create([
                'user_id' => $user->id,
                'description' => "Task {$i}",
                'status' => 'pendente'
            ]);
        }

        // Autenticar como admin para usar user_id específico
        $this->authenticateAdmin();
        $response = $this->authenticatedGet("/tasks?user_id={$user->id}&per_page=5&page=1");

        $this->assertEquals(200, $response->response->getStatusCode());

        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals(5, count($responseData['data']));
        $this->assertEquals(10, $responseData['total']);
        $this->assertEquals(1, $responseData['current_page']);
        $this->assertEquals(2, $responseData['last_page']);
    }

    /** @test */
    public function test_tasks_index_validates_invalid_user_id()
    {
        // Autenticar como admin para ter permissão de usar user_id
        $this->authenticateAdmin();
        $response = $this->authenticatedGet('/tasks?user_id=abc');

        $this->assertEquals(400, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('user_id inválido. Deve ser um número positivo.', $responseData['error']);
    }

    /** @test */
    public function test_tasks_index_validates_negative_user_id()
    {
        // Autenticar como admin para ter permissão de usar user_id
        $this->authenticateAdmin();
        $response = $this->authenticatedGet('/tasks?user_id=-1');

        $this->assertEquals(400, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('user_id inválido. Deve ser um número positivo.', $responseData['error']);
    }

    /** @test */
    public function test_tasks_index_without_user_id_returns_limited_results()
    {
        // Cria algumas tarefas para diferentes usuários
        for ($i = 1; $i <= 3; $i++) {
            $user = User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password')
            ]);

            Task::create([
                'user_id' => $user->id,
                'description' => "Task for user {$i}",
                'status' => 'pendente'
            ]);
        }

        // Autenticar como admin para ver todas as tarefas
        $this->authenticateAdmin();
        $response = $this->authenticatedGet('/tasks');

        $this->assertEquals(200, $response->response->getStatusCode());

        $responseData = json_decode($response->response->getContent(), true);
        $this->assertLessThanOrEqual(100, count($responseData)); // Máximo 100 registros
    }

    /** @test */
    public function test_tasks_get_with_valid_id()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        $task = Task::create([
            'user_id' => $user->id,
            'description' => 'Test Task',
            'status' => 'pendente'
        ]);

        // Autenticar como admin para acessar qualquer tarefa
        $this->authenticateAdmin();
        $response = $this->authenticatedGet("/tasks/{$task->id}");

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals($task->id, $responseData['data']['id']);
        $this->assertEquals($user->id, $responseData['data']['user_id']);
        $this->assertEquals('Test Task', $responseData['data']['description']);
        $this->assertEquals('pendente', $responseData['data']['status']);
    }

    /** @test */
    public function test_tasks_get_validates_invalid_id()
    {
        // Autenticar como admin 
        $this->authenticateAdmin();
        $response = $this->authenticatedGet('/tasks/abc');

        $this->assertEquals(400, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('ID inválido. O ID deve ser um número positivo.', $responseData['error']);
    }

    /** @test */
    public function test_tasks_get_validates_negative_id()
    {
        // Autenticar como admin
        $this->authenticateAdmin();
        $response = $this->authenticatedGet('/tasks/-1');

        $this->assertEquals(400, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('ID inválido. O ID deve ser um número positivo.', $responseData['error']);
    }

    /** @test */
    public function test_tasks_get_not_found()
    {
        // Usar um ID muito alto que certamente não existe
        $nonExistentId = 999999999;
        
        // Autenticar como admin
        $this->authenticateAdmin();
        $response = $this->authenticatedGet("/tasks/{$nonExistentId}");

        $this->assertEquals(404, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Tarefa não encontrada.', $responseData['error']);
        $this->assertEquals("Tarefa com ID {$nonExistentId} não existe.", $responseData['message']);
    }

    /** @test */
    public function test_tasks_index_respects_per_page_limit()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        // Autenticar como admin para usar user_id específico
        $this->authenticateAdmin();
        // Testa limite máximo de per_page
        $response = $this->authenticatedGet("/tasks?user_id={$user->id}&per_page=300");

        $this->assertEquals(200, $response->response->getStatusCode());

        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals(200, $responseData['per_page']); // Deve ser limitado a 200
    }

    /** @test */
    public function test_tasks_index_orders_by_id_desc()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        // Cria tarefas com delay para garantir diferentes timestamps
        $task1 = Task::create(['user_id' => $user->id, 'description' => 'First Task', 'status' => 'pendente']);
        $task2 = Task::create(['user_id' => $user->id, 'description' => 'Second Task', 'status' => 'pendente']);

        // Autenticar como admin para usar user_id específico
        $this->authenticateAdmin();
        $response = $this->authenticatedGet("/tasks?user_id={$user->id}");

        $this->assertEquals(200, $response->response->getStatusCode());

        $responseData = json_decode($response->response->getContent(), true);
        // O mais recente (task2) deve vir primeiro
        $this->assertEquals($task2->id, $responseData['data'][0]['id']);
        $this->assertEquals($task1->id, $responseData['data'][1]['id']);
    }
}