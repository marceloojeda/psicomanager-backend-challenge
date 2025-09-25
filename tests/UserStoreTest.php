<?php

use Laravel\Lumen\Testing\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserStoreTest extends TestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../bootstrap/app.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Limpa a tabela antes de cada teste
        User::query()->delete();
    }

    /** @test */
    public function test_can_create_user_with_valid_data()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'password123'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(201)
                ->seeJsonStructure([
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at'
                ])
                ->seeJson([
                    'name' => 'João Silva',
                    'email' => 'joao@example.com'
                ])
                ->dontSeeJson(['password']) // Não deve retornar senha
                ->dontSeeJson(['remember_token']); // Não deve retornar token

        // Verifica se o usuário foi criado no banco
        $this->seeInDatabase('users', [
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ]);

        // Verifica se a senha foi criptografada
        $user = User::where('email', 'joao@example.com')->first();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /** @test */
    public function test_validates_required_name()
    {
        $userData = [
            'email' => 'joao@example.com',
            'password' => 'password123'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['name']
                ]);

        // Verifica que o usuário não foi criado
        $this->assertFalse(
            User::where('email', 'joao@example.com')->exists(),
            'Usuário não deveria ter sido criado com dados inválidos'
        );
    }

    /** @test */
    public function test_validates_empty_name()
    {
        $userData = [
            'name' => '',
            'email' => 'joao@example.com',
            'password' => 'password123'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['name']
                ]);
    }

    /** @test */
    public function test_validates_required_email()
    {
        $userData = [
            'name' => 'João Silva',
            'password' => 'password123'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['email']
                ]);
    }

    /** @test */
    public function test_validates_email_format()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'invalid-email',
            'password' => 'password123'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['email']
                ]);
    }

    /** @test */
    public function test_validates_unique_email()
    {
        // Cria usuário existente
        User::create([
            'name' => 'Usuário Existente',
            'email' => 'existente@example.com',
            'password' => Hash::make('password')
        ]);

        $userData = [
            'name' => 'João Silva',
            'email' => 'existente@example.com',
            'password' => 'password123'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['email']
                ]);

        // Verifica que apenas um usuário com esse email existe
        $this->assertEquals(1, User::where('email', 'existente@example.com')->count());
    }

    /** @test */
    public function test_validates_required_password()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['password']
                ]);
    }

    /** @test */
    public function test_validates_minimum_password_length()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => '123' // Menor que 6 caracteres
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['password']
                ]);
    }

    /** @test */
    public function test_validates_multiple_fields_simultaneously()
    {
        $userData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => [
                        'name',
                        'email',
                        'password'
                    ]
                ]);
    }

    /** @test */
    public function test_validates_name_max_length()
    {
        $userData = [
            'name' => str_repeat('a', 256), // Maior que 255 caracteres
            'email' => 'joao@example.com',
            'password' => 'password123'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['name']
                ]);
    }

    /** @test */
    public function test_validates_email_max_length()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => str_repeat('a', 250) . '@example.com', // Maior que 255 caracteres
            'password' => 'password123'
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['email']
                ]);
    }

    /** @test */
    public function test_validates_password_max_length()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => str_repeat('a', 256) // Maior que 255 caracteres
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(422)
                ->seeJsonStructure([
                    'error',
                    'validation_errors' => ['password']
                ]);
    }

    /** @test */
    public function test_ignores_extra_fields()
    {
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'password123',
            'malicious_field' => 'should_be_ignored',
            'admin' => true
        ];

        $response = $this->json('POST', '/users', $userData);

        $response->seeStatusCode(201);

        // Verifica que apenas os campos permitidos foram salvos
        $user = User::where('email', 'joao@example.com')->first();
        $this->assertEquals('João Silva', $user->name);
        $this->assertEquals('joao@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        
        // Verifica que campos extras não foram salvos (assumindo que não existem)
        $this->assertFalse(isset($user->malicious_field));
        $this->assertFalse(isset($user->admin));
    }
}