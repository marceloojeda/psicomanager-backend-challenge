<?php

namespace Tests;

use App\Models\User;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_users_index_without_filters()
    {
        // Criar alguns usuários de teste
        User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);

        $this->get('/users');

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJsonStructure([
            '*' => [
                'id',
                'name',
                'email',
                'created_at',
                'updated_at'
            ]
        ]);
    }

    public function test_users_index_filter_by_id()
    {
        $user = User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);

        $this->get('/users?id=' . $user->id);

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJson([
            'id' => $user->id,
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);
        
        // Verificar se retornou apenas 1 usuário
        $response = json_decode($this->response->getContent(), true);
        $this->assertCount(1, $response);
    }

    public function test_users_index_filter_by_name_exact()
    {
        User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);

        $this->get('/users?name=João Silva');

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJson([
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);
        
        // Verificar se retornou apenas 1 usuário
        $response = json_decode($this->response->getContent(), true);
        $this->assertCount(1, $response);
    }

    public function test_users_index_filter_by_name_partial()
    {
        User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        User::factory()->create(['name' => 'João Santos', 'email' => 'joao.santos@example.com']);
        User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);

        $this->get('/users?name=João');

        $this->assertEquals(200, $this->response->getStatusCode());
        
        // Verificar se retornou apenas os usuários com "João" no nome
        $response = json_decode($this->response->getContent(), true);
        $this->assertCount(2, $response);
        
        foreach ($response as $user) {
            $this->assertStringContainsString('João', $user['name']);
        }
    }

    public function test_users_index_filter_by_name_case_insensitive()
    {
        User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);

        $this->get('/users?name=joão');

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJson([
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);
    }

    public function test_users_index_filter_by_both_id_and_name()
    {
        $user1 = User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        $user2 = User::factory()->create(['name' => 'João Santos', 'email' => 'joao.santos@example.com']);

        // Filtrar por ID e nome - deve retornar apenas se ambos coincidirem
        $this->get('/users?id=' . $user1->id . '&name=João Silva');

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJson([
            'id' => $user1->id,
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);
        
        // Verificar se retornou apenas 1 usuário
        $response = json_decode($this->response->getContent(), true);
        $this->assertCount(1, $response);
    }

    public function test_users_index_no_results_when_filter_not_found()
    {
        User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);

        $this->get('/users?name=Pedro');

        $this->assertEquals(200, $this->response->getStatusCode());
        
        // Verificar se retornou array vazio
        $response = json_decode($this->response->getContent(), true);
        $this->assertCount(0, $response);
        $this->assertEquals([], $response);
    }

    public function test_get_user_by_id()
    {
        $user = User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);

        $this->get('/users/' . $user->id);

        $this->assertEquals(200, $this->response->getStatusCode());
        $this->seeJson([
            'id' => $user->id,
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);
    }

    public function test_users_index_with_pagination()
    {
        // Criar 7 usuários de teste
        User::factory()->count(7)->create();

        $this->get('/users?page=1&per_page=3');

        $this->assertEquals(200, $this->response->getStatusCode());
        
        $response = json_decode($this->response->getContent(), true);
        
        // Verificar estrutura de paginação
        $this->assertArrayHasKey('current_page', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('per_page', $response);
        
        // Verificar dados da paginação
        $this->assertEquals(1, $response['current_page']);
        $this->assertEquals(3, $response['per_page']);
        $this->assertCount(3, $response['data']); // 3 usuários na primeira página
        $this->assertGreaterThanOrEqual(7, $response['total']); // Total de usuários
    }

    public function test_users_index_pagination_with_filters()
    {
        // Criar usuários específicos para teste
        User::factory()->create(['name' => 'João Silva']);
        User::factory()->create(['name' => 'João Santos']);
        User::factory()->create(['name' => 'João Pedro']);
        User::factory()->create(['name' => 'Maria Silva']);

        $this->get('/users?name=João&page=1&per_page=2');

        $this->assertEquals(200, $this->response->getStatusCode());
        
        $response = json_decode($this->response->getContent(), true);
        
        // Verificar que a paginação funciona com filtros
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals(1, $response['current_page']);
        $this->assertEquals(2, $response['per_page']);
        $this->assertCount(2, $response['data']); // 2 usuários na primeira página
        $this->assertEquals(3, $response['total']); // 3 usuários com "João"
    }

    public function test_users_index_without_pagination_parameters()
    {
        // Criar alguns usuários
        User::factory()->count(3)->create();

        $this->get('/users');

        $this->assertEquals(200, $this->response->getStatusCode());
        
        $response = json_decode($this->response->getContent(), true);
        
        // Sem parâmetros de paginação, deve retornar array simples (não paginado)
        $this->assertIsArray($response);
        $this->assertArrayNotHasKey('current_page', $response); // Não deve ter estrutura de paginação
        $this->assertGreaterThanOrEqual(3, count($response));
    }

    public function test_get_user_by_id_invalid_non_numeric()
    {
        $this->get('/users/abc');

        $this->assertEquals(400, $this->response->getStatusCode());
        $this->seeJson([
            'error' => 'ID inválido. O ID deve ser um número positivo.'
        ]);
    }

    public function test_get_user_by_id_invalid_negative()
    {
        $this->get('/users/-1');

        $this->assertEquals(400, $this->response->getStatusCode());
        $this->seeJson([
            'error' => 'ID inválido. O ID deve ser um número positivo.'
        ]);
    }

    public function test_get_user_by_id_invalid_zero()
    {
        $this->get('/users/0');

        $this->assertEquals(400, $this->response->getStatusCode());
        $this->seeJson([
            'error' => 'ID inválido. O ID deve ser um número positivo.'
        ]);
    }

    public function test_get_user_by_id_not_found()
    {
        $this->get('/users/999');

        $this->assertEquals(404, $this->response->getStatusCode());
        $this->seeJson([
            'error' => 'Usuário não encontrado.',
            'message' => 'Usuário com ID 999 não existe.'
        ]);
    }
}