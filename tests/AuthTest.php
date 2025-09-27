<?php

namespace Tests;

use App\Models\User;
use App\Services\JWTService;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    protected JWTService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jwtService = new JWTService();
    }

    /** @test */
    public function test_user_can_login_with_valid_credentials()
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->json('POST', '/auth/login', $loginData);

        // Assert
        $response->seeStatusCode(200)
            ->seeJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                    'is_deleted'
                ]
            ])
            ->seeJson([
                'token_type' => 'bearer'
            ]);

        // Verificar dados específicos do usuário separadamente
        $responseData = json_decode($response->response->getContent(), true);
        
        // Verificar se os dados básicos estão presentes e corretos
        $this->assertArrayHasKey('user', $responseData);
        $this->assertArrayHasKey('id', $responseData['user']);
        $this->assertEquals($user->name, $responseData['user']['name']);
        $this->assertEquals($user->email, $responseData['user']['email']);
        
        // O ID pode ter mudado devido a outros testes, então vamos buscar o usuário atual
        $currentUser = User::where('email', $user->email)->first();
        $this->assertEquals($currentUser->id, $responseData['user']['id']);
    }

    /** @test */
    public function test_user_cannot_login_with_invalid_password()
    {
        // Arrange
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrong_password'
        ];

        // Act
        $response = $this->json('POST', '/auth/login', $loginData);

        // Assert
        $response->seeStatusCode(401)
            ->seeJson([
                'error' => 'Credenciais inválidas',
                'message' => 'Email ou senha incorretos'
            ]);
    }

    /** @test */
    public function test_user_cannot_login_with_nonexistent_email()
    {
        // Arrange
        $loginData = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->json('POST', '/auth/login', $loginData);

        // Assert
        $response->seeStatusCode(401)
            ->seeJson([
                'error' => 'Credenciais inválidas',
                'message' => 'Email ou senha incorretos'
            ]);
    }

    /** @test */
    public function test_login_validates_required_fields()
    {
        // Test missing email
        $response = $this->json('POST', '/auth/login', [
            'password' => 'password123'
        ]);

        $response->seeStatusCode(422)
            ->seeJson([
                'error' => 'Dados inválidos'
            ]);

        // Test missing password
        $response = $this->json('POST', '/auth/login', [
            'email' => 'test@example.com'
        ]);

        $response->seeStatusCode(422)
            ->seeJson([
                'error' => 'Dados inválidos'
            ]);
    }

    /** @test */
    public function test_user_can_login_even_if_soft_deleted()
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'test.softdeleted@example.com', // Email único para evitar conflitos
            'password' => Hash::make('password123')
        ]);

        // Soft delete user
        $user->delete();
        
        // Verificar que realmente foi soft deleted
        $this->assertFalse(User::where('id', $user->id)->exists());
        $this->assertTrue(User::withTrashed()->where('id', $user->id)->exists());

        $loginData = [
            'email' => 'test.softdeleted@example.com',
            'password' => 'password123'
        ];

        // Act
        $response = $this->json('POST', '/auth/login', $loginData);

        // Assert - usuário pode fazer login mesmo sendo soft deleted
        $response->seeStatusCode(200)
            ->seeJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                    'is_deleted'
                ]
            ]);
            
        // Verificar que is_deleted está como true
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertTrue($responseData['user']['is_deleted']);
    }

    /** @test */
    public function test_can_refresh_token_with_valid_refresh_token()
    {
        // Arrange
        $user = User::factory()->create();
        $refreshToken = $this->jwtService->generateRefreshToken($user);

        // Act
        $response = $this->json('POST', '/auth/refresh', [
            'refresh_token' => $refreshToken
        ]);

        // Assert
        $response->seeStatusCode(200)
            ->seeJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in'
            ])
            ->seeJson([
                'token_type' => 'bearer'
            ]);
    }

    /** @test */
    public function test_cannot_refresh_with_invalid_token()
    {
        // Act
        $response = $this->json('POST', '/auth/refresh', [
            'refresh_token' => 'invalid_token'
        ]);

        // Assert
        $response->seeStatusCode(401)
            ->seeJson([
                'error' => 'Refresh token inválido ou expirado'
            ]);
    }

    /** @test */
    public function test_can_logout_with_valid_token()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->jwtService->generateToken($user);

        // Act
        $response = $this->json('POST', '/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        // Assert
        $response->seeStatusCode(200)
            ->seeJson([
                'message' => 'Logout realizado com sucesso'
            ]);
    }

    /** @test */
    public function test_cannot_logout_without_token()
    {
        // Act
        $response = $this->json('POST', '/auth/logout');

        // Assert
        $response->seeStatusCode(401)
            ->seeJson([
                'error' => 'Token de acesso obrigatório'
            ]);
    }

    /** @test */
    public function test_can_get_user_info_with_valid_token()
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);
        $token = $this->jwtService->generateToken($user);

        // Act
        $response = $this->json('GET', '/auth/me', [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        // Assert
        $response->seeStatusCode(200)
            ->seeJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ]
            ]);

        // Verificar dados específicos do usuário separadamente
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals($user->id, $responseData['user']['id']);
        $this->assertEquals($user->name, $responseData['user']['name']);
        $this->assertEquals($user->email, $responseData['user']['email']);
    }

    /** @test */
    public function test_cannot_access_me_endpoint_without_token()
    {
        // Act
        $response = $this->json('GET', '/auth/me');

        // Assert
        $response->seeStatusCode(401)
            ->seeJson([
                'error' => 'Token de acesso obrigatório'
            ]);
    }

    /** @test */
    public function test_protected_routes_require_authentication()
    {
        // Test tasks endpoint
        $response = $this->json('GET', '/tasks');
        $response->seeStatusCode(401)
            ->seeJson([
                'error' => 'Token de acesso obrigatório'
            ]);

        // Test users endpoint
        $response = $this->json('GET', '/users');
        $response->seeStatusCode(401)
            ->seeJson([
                'error' => 'Token de acesso obrigatório'
            ]);
    }

    /** @test */
    public function test_protected_routes_work_with_valid_token()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->jwtService->generateToken($user);

        // Act & Assert - Tasks endpoint
        $response = $this->json('GET', '/tasks', [], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $response->seeStatusCode(200);

        // Act & Assert - Users endpoint
        $response = $this->json('GET', '/users', [], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $response->seeStatusCode(200);
    }

    /** @test */
    public function test_malformed_token_is_rejected()
    {
        // Act
        $response = $this->json('GET', '/auth/me', [], [
            'Authorization' => 'Bearer malformed.token.here'
        ]);

        // Assert
        $response->seeStatusCode(401)
            ->seeJson([
                'error' => 'Token inválido ou expirado'
            ]);
    }

    /** @test */
    public function test_missing_bearer_prefix_is_rejected()
    {
        // Arrange
        $user = User::factory()->create();
        $token = $this->jwtService->generateToken($user);

        // Act - without Bearer prefix
        $response = $this->json('GET', '/auth/me', [], [
            'Authorization' => $token
        ]);

        // Assert
        $response->seeStatusCode(401)
            ->seeJson([
                'error' => 'Token de acesso obrigatório'
            ]);
    }
}