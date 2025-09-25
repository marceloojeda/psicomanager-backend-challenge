<?php

use Laravel\Lumen\Testing\TestCase;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TaskControllerTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Limpa as tabelas antes de cada teste
        Task::query()->delete();
        User::query()->delete();
    }

    /** @test */
    public function test_tasks_index_with_valid_user_id()
    {
        // Cria usuário de teste
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        // Cria algumas tarefas
        Task::create(['user_id' => $user->id, 'description' => 'Task 1', 'status' => 'pendente']);
        Task::create(['user_id' => $user->id, 'description' => 'Task 2', 'status' => 'em progresso']);

        $response = $this->json('GET', "/tasks?user_id={$user->id}");

        $response->seeStatusCode(200)
                ->seeJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'description',
                            'status',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'current_page',
                    'per_page',
                    'total'
                ]);

        $data = json_decode($response->response->getContent(), true);
        $this->assertEquals(2, count($data['data']));
        $this->assertEquals(2, $data['total']);
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

        $response = $this->json('GET', "/tasks?user_id={$user->id}&per_page=5&page=1");

        $response->seeStatusCode(200);

        $data = json_decode($response->response->getContent(), true);
        $this->assertEquals(5, count($data['data']));
        $this->assertEquals(10, $data['total']);
        $this->assertEquals(1, $data['current_page']);
        $this->assertEquals(2, $data['last_page']);
    }

    /** @test */
    public function test_tasks_index_validates_invalid_user_id()
    {
        $response = $this->json('GET', '/tasks?user_id=abc');

        $response->seeStatusCode(400)
                ->seeJson([
                    'error' => 'user_id inválido. Deve ser um número positivo.'
                ]);
    }

    /** @test */
    public function test_tasks_index_validates_negative_user_id()
    {
        $response = $this->json('GET', '/tasks?user_id=-1');

        $response->seeStatusCode(400)
                ->seeJson([
                    'error' => 'user_id inválido. Deve ser um número positivo.'
                ]);
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

        $response = $this->json('GET', '/tasks');

        $response->seeStatusCode(200);

        $data = json_decode($response->response->getContent(), true);
        $this->assertLessThanOrEqual(100, count($data)); // Máximo 100 registros
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

        $response = $this->json('GET', "/tasks/{$task->id}");

        $response->seeStatusCode(200)
                ->seeJson([
                    'id' => $task->id,
                    'user_id' => $user->id,
                    'description' => 'Test Task',
                    'status' => 'pendente'
                ]);
    }

    /** @test */
    public function test_tasks_get_validates_invalid_id()
    {
        $response = $this->json('GET', '/tasks/abc');

        $response->seeStatusCode(400)
                ->seeJson([
                    'error' => 'ID inválido. O ID deve ser um número positivo.'
                ]);
    }

    /** @test */
    public function test_tasks_get_validates_negative_id()
    {
        $response = $this->json('GET', '/tasks/-1');

        $response->seeStatusCode(400)
                ->seeJson([
                    'error' => 'ID inválido. O ID deve ser um número positivo.'
                ]);
    }

    /** @test */
    public function test_tasks_get_not_found()
    {
        $response = $this->json('GET', '/tasks/999');

        $response->seeStatusCode(404)
                ->seeJson([
                    'error' => 'Tarefa não encontrada.',
                    'message' => 'Tarefa com ID 999 não existe.'
                ]);
    }

    /** @test */
    public function test_tasks_index_respects_per_page_limit()
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        // Testa limite máximo de per_page
        $response = $this->json('GET', "/tasks?user_id={$user->id}&per_page=300");

        $response->seeStatusCode(200);

        $data = json_decode($response->response->getContent(), true);
        $this->assertEquals(200, $data['per_page']); // Deve ser limitado a 200
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

        $response = $this->json('GET', "/tasks?user_id={$user->id}");

        $response->seeStatusCode(200);

        $data = json_decode($response->response->getContent(), true);
        // O mais recente (task2) deve vir primeiro
        $this->assertEquals($task2->id, $data['data'][0]['id']);
        $this->assertEquals($task1->id, $data['data'][1]['id']);
    }
}