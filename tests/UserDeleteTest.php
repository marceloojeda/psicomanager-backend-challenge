<?php

use Laravel\Lumen\Testing\TestCase;
use App\Models\User;
use App\Models\Task;
use Illuminate\Support\Facades\Hash;

class UserDeleteTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function test_delete_user_success()
    {
        // Cria usuário sem tarefas
        $user = User::create([
            'name' => 'Usuário Teste',
            'email' => 'teste@delete.com',
            'password' => Hash::make('password123')
        ]);

        $response = $this->json('DELETE', "/users/{$user->id}");

        $response->seeStatusCode(200)
                ->seeJson([
                    'message' => 'Usuário excluído com sucesso (soft delete).'
                ]);

        // Verifica se o usuário foi soft deleted (não aparece em consultas normais)
        $this->assertNull(User::find($user->id));
        // Mas ainda existe quando incluímos soft deleted
        $this->assertNotNull(User::withTrashed()->find($user->id));
    }

    /** @test */
    public function test_delete_user_with_tasks_fails()
    {
        // Cria usuário
        $user = User::create([
            'name' => 'Usuário com Tarefas',
            'email' => 'comtasks@delete.com',
            'password' => Hash::make('password123')
        ]);

        // Cria tarefas para o usuário
        Task::create([
            'user_id' => $user->id,
            'description' => 'Tarefa 1',
            'status' => 'pendente'
        ]);

        Task::create([
            'user_id' => $user->id,
            'description' => 'Tarefa 2',
            'status' => 'em progresso'
        ]);

        $response = $this->json('DELETE', "/users/{$user->id}");

        $response->seeStatusCode(200)
                ->seeJson([
                    'message' => 'Usuário excluído com sucesso (soft delete).'
                ]);

        // Verifica soft delete: usuário não aparece em consultas normais
        $this->assertNull(User::find($user->id));
        // Mas existe quando incluímos soft deleted
        $this->assertNotNull(User::withTrashed()->find($user->id));

        // Verifica se as tarefas ainda existem
        $this->assertEquals(2, Task::where('user_id', $user->id)->count());
    }

    /** @test */
    public function test_delete_user_with_many_tasks_shows_correct_count()
    {
        // Cria usuário
        $user = User::create([
            'name' => 'Usuário com Muitas Tarefas',
            'email' => 'muitas@tasks.com',
            'password' => Hash::make('password123')
        ]);

        // Cria 50 tarefas para o usuário
        for ($i = 1; $i <= 50; $i++) {
            Task::create([
                'user_id' => $user->id,
                'description' => "Tarefa {$i}",
                'status' => 'pendente'
            ]);
        }

        $response = $this->json('DELETE', "/users/{$user->id}");

        $response->seeStatusCode(200)
                ->seeJson([
                    'message' => 'Usuário excluído com sucesso (soft delete).'
                ]);

        // Verifica que as tarefas foram preservadas na resposta
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertStringContainsString('50 tarefas associadas', $responseData['tasks_preserved']);
    }

    /** @test */
    public function test_delete_user_validates_invalid_id()
    {
        $response = $this->json('DELETE', '/users/abc');

        $response->seeStatusCode(400)
                ->seeJson([
                    'error' => 'ID inválido. O ID deve ser um número positivo.'
                ]);
    }

    /** @test */
    public function test_delete_user_validates_negative_id()
    {
        $response = $this->json('DELETE', '/users/-1');

        $response->seeStatusCode(400)
                ->seeJson([
                    'error' => 'ID inválido. O ID deve ser um número positivo.'
                ]);
    }

    /** @test */
    public function test_delete_user_validates_zero_id()
    {
        $response = $this->json('DELETE', '/users/0');

        $response->seeStatusCode(400)
                ->seeJson([
                    'error' => 'ID inválido. O ID deve ser um número positivo.'
                ]);
    }

    /** @test */
    public function test_delete_user_not_found()
    {
        $response = $this->json('DELETE', '/users/99999');

        $response->seeStatusCode(404)
                ->seeJsonStructure([
                    'error',
                    'message'
                ])
                ->seeJson([
                    'error' => 'Usuário não encontrado.'
                ]);

        $responseData = json_decode($response->response->getContent(), true);
        $this->assertStringContainsString('99999', $responseData['message']);
    }

    /** @test */
    public function test_delete_preserves_referential_integrity()
    {
        // Cria usuário com tarefas
        $user = User::create([
            'name' => 'Usuário Integridade',
            'email' => 'integridade@test.com',
            'password' => Hash::make('password123')
        ]);

        $task = Task::create([
            'user_id' => $user->id,
            'description' => 'Tarefa importante',
            'status' => 'pendente'
        ]);

        // Tenta excluir o usuário
        $response = $this->json('DELETE', "/users/{$user->id}");

        $response->seeStatusCode(200);

        // Verifica soft delete: usuário não aparece em consultas normais
        $this->assertNull(User::find($user->id));
        // Mas existe quando incluímos soft deleted
        $this->assertNotNull(User::withTrashed()->find($user->id));
        // Task permanece intacta
        $this->assertNotNull(Task::find($task->id));
        $this->assertEquals($user->id, Task::find($task->id)->user_id);
    }
}