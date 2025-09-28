<?php

namespace Tests;

use Laravel\Lumen\Testing\TestCase as BaseTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    protected $authUser;
    protected $authToken;

    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        return require __DIR__.'/../bootstrap/app.php';
    }

    /**
     * Create and authenticate a test user, returning the JWT token
     */
    protected function authenticateUser($userData = [])
    {
        $defaultData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ];

        $userData = array_merge($defaultData, $userData);

        // Create user
        $this->authUser = User::create($userData);

        // Login to get JWT token
        $loginResponse = $this->json('POST', '/auth/login', [
            'email' => $userData['email'],
            'password' => 'password123' // Use plain password for login
        ]);

        $loginData = json_decode($loginResponse->response->getContent(), true);
        
        if (isset($loginData['access_token'])) {
            $this->authToken = $loginData['access_token'];
            return $this->authToken;
        }

        throw new \Exception('Failed to authenticate user: ' . $loginResponse->response->getContent());
    }

    /**
     * Authenticate as admin user
     */
    protected function authenticateAdmin($userData = [])
    {
        $defaultData = [
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin'
        ];

        $userData = array_merge($defaultData, $userData);

        // Create admin user
        $this->authUser = User::create($userData);

        // Login to get JWT token
        $loginResponse = $this->json('POST', '/auth/login', [
            'email' => $userData['email'],
            'password' => 'password123' // Use plain password for login
        ]);

        $loginData = json_decode($loginResponse->response->getContent(), true);
        
        if (isset($loginData['access_token'])) {
            $this->authToken = $loginData['access_token'];
            return $this->authToken;
        }

        throw new \Exception('Failed to authenticate admin user: ' . $loginResponse->response->getContent());
    }

    /**
     * Make authenticated GET request
     */
    protected function authenticatedGet($uri, array $headers = [])
    {
        if (!$this->authToken) {
            $this->authenticateUser();
        }

        $headers['Authorization'] = 'Bearer ' . $this->authToken;
        return $this->json('GET', $uri, [], $headers);
    }

    /**
     * Make authenticated POST request
     */
    protected function authenticatedPost($uri, array $data = [], array $headers = [])
    {
        if (!$this->authToken) {
            $this->authenticateUser();
        }

        $headers['Authorization'] = 'Bearer ' . $this->authToken;
        return $this->json('POST', $uri, $data, $headers);
    }

    /**
     * Make authenticated PUT request
     */
    protected function authenticatedPut($uri, array $data = [], array $headers = [])
    {
        if (!$this->authToken) {
            $this->authenticateUser();
        }

        $headers['Authorization'] = 'Bearer ' . $this->authToken;
        return $this->json('PUT', $uri, $data, $headers);
    }

    /**
     * Make authenticated PATCH request
     */
    protected function authenticatedPatch($uri, array $data = [], array $headers = [])
    {
        if (!$this->authToken) {
            $this->authenticateUser();
        }

        $headers['Authorization'] = 'Bearer ' . $this->authToken;
        return $this->json('PATCH', $uri, $data, $headers);
    }

    /**
     * Make authenticated DELETE request
     */
    protected function authenticatedDelete($uri, array $data = [], array $headers = [])
    {
        if (!$this->authToken) {
            $this->authenticateUser();
        }

        $headers['Authorization'] = 'Bearer ' . $this->authToken;
        return $this->json('DELETE', $uri, $data, $headers);
    }

    /**
     * Get authentication headers
     */
    protected function getAuthHeaders()
    {
        if (!$this->authToken) {
            $this->authenticateUser();
        }

        return ['Authorization' => 'Bearer ' . $this->authToken];
    }
}
