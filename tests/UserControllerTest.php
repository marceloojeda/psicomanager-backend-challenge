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

        $response = $this->authenticatedGet('/users');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        
        // Sem parâmetros de paginação, retorna array direto
        $this->assertIsArray($responseData);
        $this->assertGreaterThan(0, count($responseData));
        
        // Verificar que campos sensíveis não estão sendo expostos
        foreach ($responseData as $user) {
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

        $response = $this->authenticatedGet('/users?id=' . $user->id);

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        
        // Verificar se retornou apenas 1 usuário
        $this->assertCount(1, $responseData);
        
        // Verificar dados do usuário
        $this->assertEquals($user->id, $responseData[0]['id']);
        $this->assertEquals('João Silva', $responseData[0]['name']);
        $this->assertEquals('joao@example.com', $responseData[0]['email']);
    }

    public function test_users_index_filter_by_name_exact()
    {
        User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);

        $response = $this->authenticatedGet('/users?name=João Silva');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertGreaterThanOrEqual(1, count($responseData));
        
        // Verificar se existe ao menos um usuário com nome "João Silva"
        $found = false;
        foreach ($responseData as $user) {
            if ($user['name'] === 'João Silva') {
                $found = true;
                $this->assertEquals('joao@example.com', $user['email']);
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

        $response = $this->authenticatedGet('/users?name=João');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        // Verificar se retornou apenas os usuários com "João" no nome
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertGreaterThanOrEqual(2, count($responseData));
        
        foreach ($responseData as $user) {
            $this->assertStringContainsString('João', $user['name']);
        }
    }

    public function test_users_index_filter_by_name_case_insensitive()
    {
        User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);

        $response = $this->authenticatedGet('/users?name=joão');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        
        // Verificar se existe ao menos um usuário com nome "João Silva"
        $found = false;
        foreach ($responseData as $user) {
            if ($user['name'] === 'João Silva') {
                $found = true;
                $this->assertEquals('joao@example.com', $user['email']);
                break;
            }
        }
        $this->assertTrue($found, 'Usuário João Silva deve estar presente na resposta');
    }

    public function test_users_index_filter_by_both_id_and_name()
    {
        $user1 = User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);
        $user2 = User::factory()->create(['name' => 'João Santos', 'email' => 'joao.santos@example.com']);

        // Filtrar por ID e nome - deve retornar apenas se ambos coincidirem
        $response = $this->authenticatedGet('/users?id=' . $user1->id . '&name=João Silva');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        
        // Verificar se retornou apenas 1 usuário
        $this->assertCount(1, $responseData);
        
        // Verificar dados
        $this->assertEquals($user1->id, $responseData[0]['id']);
        $this->assertEquals('João Silva', $responseData[0]['name']);
        $this->assertEquals('joao@example.com', $responseData[0]['email']);
    }

    public function test_users_index_no_results_when_filter_not_found()
    {
        User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);

        // Use um nome único que garantidamente não existe
        $uniqueName = 'UsuarioInexistente' . time();
        $response = $this->authenticatedGet("/users?name={$uniqueName}");

        $this->assertEquals(200, $response->response->getStatusCode());
        
        // Verificar se retornou array vazio
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertCount(0, $responseData);
    }

    public function test_get_user_by_id()
    {
        $user = User::factory()->create(['name' => 'João Silva', 'email' => 'joao@example.com']);

        $response = $this->authenticatedGet('/users/' . $user->id);

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        
        // Verificar dados do usuário
        $this->assertEquals($user->id, $responseData['data']['id']);
        $this->assertEquals('João Silva', $responseData['data']['name']);
        $this->assertEquals('joao@example.com', $responseData['data']['email']);
    }

    public function test_users_index_with_pagination()
    {
        // Criar 7 usuários de teste
        User::factory()->count(7)->create();

        $response = $this->authenticatedGet('/users?page=1&per_page=3');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        
        // Verificar estrutura de paginação
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('current_page', $responseData);
        $this->assertArrayHasKey('per_page', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('last_page', $responseData);
        
        // Verificar dados da paginação
        $this->assertEquals(1, $responseData['current_page']);
        $this->assertEquals(3, $responseData['per_page']);
        $this->assertLessThanOrEqual(3, count($responseData['data'])); // Máximo 3 usuários na primeira página
        $this->assertGreaterThanOrEqual(7, $responseData['total']); // Total de usuários
    }

    public function test_users_index_pagination_with_filters()
    {
        // Criar usuários específicos para teste
        User::factory()->create(['name' => 'João Silva']);
        User::factory()->create(['name' => 'João Santos']);
        User::factory()->create(['name' => 'João Pedro']);
        User::factory()->create(['name' => 'Maria Silva']);

        // Testar paginação com filtro de nome
        $response = $this->authenticatedGet('/users?name=João&page=1&per_page=2');

        $this->assertEquals(200, $response->response->getStatusCode());
        $responseData = json_decode($response->response->getContent(), true);
        
        // Verificar estrutura de paginação
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('current_page', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        
        // Verificar que todos os resultados contêm "João" no nome
        foreach ($responseData['data'] as $user) {
            $this->assertStringContainsString('João', $user['name']);
        }
        
        // Verificar que o total é pelo menos 3 (ou o que encontramos)
        $this->assertGreaterThanOrEqual(3, $responseData['total']);

        // Verificar que a paginação funciona com filtros
        $this->assertEquals(1, $responseData['current_page']);
        $this->assertEquals(2, $responseData['per_page']);
        $this->assertLessThanOrEqual(2, count($responseData['data'])); // máximo 2 usuários na primeira página
    }

    public function test_users_index_without_pagination_parameters()
    {
        // Criar alguns usuários
        User::factory()->count(3)->create();

        $response = $this->authenticatedGet('/users');

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        
        // Sem parâmetros de paginação, deve retornar array simples
        $this->assertIsArray($responseData);
        $this->assertGreaterThanOrEqual(3, count($responseData));
    }

    public function test_get_user_by_id_invalid_non_numeric()
    {
        $response = $this->authenticatedGet('/users/abc');

        $this->assertEquals(400, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('ID inválido. O ID deve ser um número positivo.', $responseData['error']);
    }

    public function test_get_user_by_id_invalid_negative()
    {
        $response = $this->authenticatedGet('/users/-1');

        $this->assertEquals(400, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('ID inválido. O ID deve ser um número positivo.', $responseData['error']);
    }

    public function test_get_user_by_id_invalid_zero()
    {
        $response = $this->authenticatedGet('/users/0');

        $this->assertEquals(400, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('ID inválido. O ID deve ser um número positivo.', $responseData['error']);
    }

    public function test_get_user_by_id_not_found()
    {
        $response = $this->authenticatedGet('/users/999');

        $this->assertEquals(404, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Usuário não encontrado.', $responseData['error']);
        $this->assertEquals('Usuário com ID 999 não existe.', $responseData['message']);
    }

    public function test_get_user_by_id_does_not_expose_sensitive_data()
    {
        $user = User::factory()->create([
            'name' => 'João Silva', 
            'email' => 'joao@example.com',
            'password' => app('hash')->make('senha123')
        ]);

        $response = $this->authenticatedGet('/users/' . $user->id);

        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        
        // Verificar estrutura com UserResource
        $this->assertArrayHasKey('data', $responseData);
        
        $userData = $responseData['data'];
        
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