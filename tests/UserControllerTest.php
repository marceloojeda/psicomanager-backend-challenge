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
        
        $response = json_decode($this->response->getContent(), true);
        
        // Sem parâmetros de paginação, retorna array direto
        $this->assertIsArray($response);
        $this->assertGreaterThan(0, count($response));
        
        // Verificar que campos sensíveis não estão sendo expostos
        foreach ($response as $user) {
            $this->assertArrayNotHasKey('password', $user);
            $this->assertArrayNotHasKey('remember_token', $user);
            // Verificar que campos permitidos estão presentes
            $this->assertArrayHasKey('id', $user);
            $this->assertArrayHasKey('name', $user);
            $this->assertArrayHasKey('email', $user);
            $this->assertArrayHasKey('created_at', $user);
            $this->assertArrayHasKey('updated_at', $user);
        }
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
        
        // Verificar se o usuário está presente na resposta
        $response = json_decode($this->response->getContent(), true);
        $this->assertGreaterThanOrEqual(1, count($response));
        
        // Verificar se existe ao menos um usuário com nome "João Silva"
        $found = false;
        foreach ($response as $user) {
            if ($user['name'] === 'João Silva') {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Usuário João Silva deve estar presente na resposta');
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
        $this->assertIsArray($response);
        $this->assertGreaterThanOrEqual(2, count($response));
        
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

        // Use um nome único que garantidamente não existe
        $uniqueName = 'UsuarioInexistente' . time();
        $this->get("/users?name={$uniqueName}");

        $this->assertEquals(200, $this->response->getStatusCode());
        
        // Verificar se retornou array vazio
        $response = json_decode($this->response->getContent(), true);
        $this->assertIsArray($response);
        $this->assertCount(0, $response);
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
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('current_page', $response);
        $this->assertArrayHasKey('per_page', $response);
        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('last_page', $response);
        
        // Verificar dados da paginação
        $this->assertEquals(1, $response['current_page']);
        $this->assertEquals(3, $response['per_page']);
        $this->assertLessThanOrEqual(3, count($response['data'])); // Máximo 3 usuários na primeira página
        $this->assertGreaterThanOrEqual(7, $response['total']); // Total de usuários
    }

    public function test_users_index_pagination_with_filters()
    {
        // Criar usuários específicos para teste
        User::factory()->create(['name' => 'João Silva']);
        User::factory()->create(['name' => 'João Santos']);
        User::factory()->create(['name' => 'João Pedro']);
        User::factory()->create(['name' => 'Maria Silva']);

                // Testar paginação com filtro de nome
        $this->get('/users?name=João&page=1&per_page=2');

        $this->assertEquals(200, $this->response->getStatusCode());
        $response = json_decode($this->response->getContent(), true);
        
        // Verificar estrutura de paginação
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('current_page', $response);
        $this->assertArrayHasKey('total', $response);
        
        // Verificar que todos os resultados contêm "João" no nome
        foreach ($response['data'] as $user) {
            $this->assertStringContainsString('João', $user['name']);
        }
        
        // Verificar que o total é pelo menos 3 (ou o que encontramos)
        $this->assertGreaterThanOrEqual(3, $response['total']);

        // Verificar que a paginação funciona com filtros
        $this->assertEquals(1, $response['current_page']);
        $this->assertEquals(2, $response['per_page']);
        $this->assertLessThanOrEqual(2, count($response['data'])); // máximo 2 usuários na primeira página
    }

    public function test_users_index_without_pagination_parameters()
    {
        // Criar alguns usuários
        User::factory()->count(3)->create();

        $this->get('/users');

        $this->assertEquals(200, $this->response->getStatusCode());
        
        $response = json_decode($this->response->getContent(), true);
        
        // Sem parâmetros de paginação, deve retornar array simples
        $this->assertIsArray($response);
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

    public function test_get_user_by_id_does_not_expose_sensitive_data()
    {
        $user = User::factory()->create([
            'name' => 'João Silva', 
            'email' => 'joao@example.com',
            'password' => app('hash')->make('senha123')
        ]);

        $this->get('/users/' . $user->id);

        $this->assertEquals(200, $this->response->getStatusCode());
        
        $response = json_decode($this->response->getContent(), true);
        
        // Verificar estrutura com UserResource
        $this->assertArrayHasKey('data', $response);
        
        $userData = $response['data'];
        
        // Verificar que campos sensíveis não estão sendo expostos
        $this->assertArrayNotHasKey('password', $userData);
        $this->assertArrayNotHasKey('remember_token', $userData);
        
        // Verificar que campos permitidos estão presentes
        $this->assertArrayHasKey('id', $userData);
        $this->assertArrayHasKey('name', $userData);
        $this->assertArrayHasKey('email', $userData);
        $this->assertArrayHasKey('created_at', $userData);
        $this->assertArrayHasKey('updated_at', $userData);
        
        // Verificar valores corretos
        $this->assertEquals($user->id, $userData['id']);
        $this->assertEquals('João Silva', $userData['name']);
        $this->assertEquals('joao@example.com', $userData['email']);
    }
}