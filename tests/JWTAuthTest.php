<?php

use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class JWTAuthTest extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar usuário de teste
        User::query()->forceDelete(); // Limpar todos os usuários
        $this->testUser = User::create([
            'name' => 'Test User',
            'email' => 'test@jwt.com',
            'password' => Hash::make('password123')
        ]);
    }

    protected function tearDown(): void
    {
        User::query()->forceDelete(); // Limpar após teste
        parent::tearDown();
    }

    public function testLoginWithValidCredentials()
    {
        $response = $this->post('/auth/login', [
            'email' => 'test@jwt.com',
            'password' => 'password123'
        ]);

        $response->assertResponseStatus(200);
        
        $content = json_decode($response->response->getContent(), true);
        
        $this->assertArrayHasKey('access_token', $content);
        $this->assertArrayHasKey('refresh_token', $content);
        $this->assertArrayHasKey('token_type', $content);
        $this->assertArrayHasKey('expires_in', $content);
        $this->assertEquals('bearer', $content['token_type']);
    }

    public function testLoginWithInvalidCredentials()
    {
        $response = $this->post('/auth/login', [
            'email' => 'test@jwt.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertResponseStatus(401);
        
        $content = json_decode($response->response->getContent(), true);
        $this->assertArrayHasKey('error', $content);
    }

    public function testAccessProtectedRouteWithValidToken()
    {
        // Fazer login
        $loginResponse = $this->post('/auth/login', [
            'email' => 'test@jwt.com',
            'password' => 'password123'
        ]);

        $loginContent = json_decode($loginResponse->response->getContent(), true);
        $token = $loginContent['access_token'];

        // Acessar rota protegida
        $response = $this->get('/auth/me', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertResponseStatus(200);
        
        $content = json_decode($response->response->getContent(), true);
        $this->assertArrayHasKey('user', $content);
        $this->assertEquals('test@jwt.com', $content['user']['email']);
    }

    public function testAccessProtectedRouteWithoutToken()
    {
        $response = $this->get('/auth/me');
        $response->assertResponseStatus(401);
    }

    public function testRefreshToken()
    {
        // Fazer login
        $loginResponse = $this->post('/auth/login', [
            'email' => 'test@jwt.com',
            'password' => 'password123'
        ]);

        $loginContent = json_decode($loginResponse->response->getContent(), true);
        $refreshToken = $loginContent['refresh_token'];

        // Usar refresh token
        $response = $this->post('/auth/refresh', [
            'refresh_token' => $refreshToken
        ]);

        $response->assertResponseStatus(200);
        
        $content = json_decode($response->response->getContent(), true);
        $this->assertArrayHasKey('access_token', $content);
        $this->assertArrayHasKey('refresh_token', $content);
    }
}