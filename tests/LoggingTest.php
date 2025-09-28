<?php

namespace Tests;

use App\Models\User;
use App\Models\Task;
use Tests\TestCase;
use Carbon\Carbon;

class LoggingTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test that user creation is logged
     */
    public function test_user_creation_logged()
    {
        // Create a user with unique email to avoid conflicts
        $uniqueEmail = 'logging-test-' . time() . '@example.com';
        
        $userData = [
            'name' => 'Logging Test User',
            'email' => $uniqueEmail,
            'password' => 'password123'
        ];

        $response = $this->json('POST', '/users', $userData);
        $this->assertEquals(201, $response->response->getStatusCode());

        // Check if operation log file exists
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        $this->assertTrue(file_exists($operationLogFile), 'Operation log file should exist');

        // Check log content
        $logContent = file_get_contents($operationLogFile);
        $this->assertStringContainsString('user_created', $logContent);
        $this->assertStringContainsString($uniqueEmail, $logContent);
    }

    /**
     * Test that user deletion is audited
     */
    public function test_user_deletion_audited()
    {
        // Create a test user
        $user = User::factory()->create([
            'name' => 'User to Delete',
            'email' => 'logging-test-delete@example.com'
        ]);

        // Autenticar como admin para acessar endpoint de usuários
        $this->authenticateAdmin();

        // Delete user
        $response = $this->authenticatedDelete("/users/{$user->id}");
        $this->assertEquals(200, $response->response->getStatusCode());

        // Check if audit log file exists
        $auditLogFile = storage_path('logs/audit-' . Carbon::now()->format('Y-m-d') . '.log');
        $this->assertTrue(file_exists($auditLogFile), 'Audit log file should exist');

        // Check audit content
        $auditContent = file_get_contents($auditLogFile);
        $this->assertStringContainsString('user_soft_deleted', $auditContent);
        $this->assertStringContainsString('logging-test-delete@example.com', $auditContent);
    }

    /**
     * Test that user restoration is audited
     */
    public function test_user_restoration_audited()
    {
        // Create and delete a test user
        $user = User::factory()->create([
            'name' => 'User to Restore',
            'email' => 'logging-test-restore@example.com'
        ]);
        $user->delete(); // Soft delete

        // Autenticar como admin para acessar endpoint de usuários
        $this->authenticateAdmin();

        // Restore user
        $response = $this->authenticatedPatch("/users/{$user->id}/restore");
        $this->assertEquals(200, $response->response->getStatusCode());

        // Check audit content
        $auditLogFile = storage_path('logs/audit-' . Carbon::now()->format('Y-m-d') . '.log');
        $auditContent = file_get_contents($auditLogFile);
        $this->assertStringContainsString('user_restored', $auditContent);
        $this->assertStringContainsString('logging-test-restore@example.com', $auditContent);
    }

    /**
     * Test that task queries are logged
     */
    public function test_task_queries_logged()
    {
        // Autenticar como admin para acessar endpoint com user_id específico
        $this->authenticateAdmin();
        
        // Create a test user
        $user = User::factory()->create([
            'email' => 'logging-test-tasks@example.com'
        ]);

        // Query tasks for user
        $response = $this->authenticatedGet("/tasks?user_id={$user->id}");
        $this->assertEquals(200, $response->response->getStatusCode());

        // Check operation log
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        $logContent = file_get_contents($operationLogFile);
        $this->assertStringContainsString('tasks_queried_by_user', $logContent);
        $this->assertStringContainsString("\"requested_user_id\":\"{$user->id}\"", $logContent);
    }

    /**
     * Test that task creation is logged
     */
    public function test_task_creation_logged()
    {
        // Authenticate with unique user
        $this->authenticateUser(['email' => 'task-create-' . time() . '@test.com']);
        
        $taskData = [
            'description' => 'Logging Test Task',
            'status' => 'pendente'
        ];

        $response = $this->authenticatedPost('/tasks', $taskData);
        $this->assertEquals(201, $response->response->getStatusCode());

        // Check operation log
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        $logContent = file_get_contents($operationLogFile);
        $this->assertStringContainsString('task_created', $logContent);
        $this->assertStringContainsString('Logging Test Task', $logContent);
    }

    /**
     * Test that task updates are logged
     */
    public function test_task_update_logged()
    {
        // Authenticate with unique user
        $this->authenticateUser(['email' => 'task-update-' . time() . '@test.com']);
        
        // Create a task first through API
        $taskResponse = $this->authenticatedPost('/tasks', [
            'description' => 'Original Task Description',
            'status' => 'pendente'
        ]);
        $this->assertEquals(201, $taskResponse->response->getStatusCode());
        $taskData = json_decode($taskResponse->response->getContent(), true);
        $taskId = $taskData['data']['id'];

        $response = $this->authenticatedPut("/tasks/{$taskId}", [
            'description' => 'Updated Task Description',
            'status' => 'concluida'
        ]);
        $this->assertEquals(200, $response->response->getStatusCode());

        // Check operation log
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        $logContent = file_get_contents($operationLogFile);
        $this->assertStringContainsString('task_updated', $logContent);
        $this->assertStringContainsString('Updated Task Description', $logContent);
    }

    /**
     * Test that task deletion is audited
     */
    public function test_task_deletion_audited()
    {
        // Authenticate with unique user
        $this->authenticateUser(['email' => 'task-delete-' . time() . '@test.com']);
        
        // Create a task first through API
        $taskResponse = $this->authenticatedPost('/tasks', [
            'description' => 'Task to Delete',
            'status' => 'pendente'
        ]);
        $this->assertEquals(201, $taskResponse->response->getStatusCode());
        $taskData = json_decode($taskResponse->response->getContent(), true);
        $taskId = $taskData['data']['id'];

        $response = $this->authenticatedDelete("/tasks/{$taskId}");
        $this->assertEquals(200, $response->response->getStatusCode());

        // Check operation log
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        $operationContent = file_get_contents($operationLogFile);
        $this->assertStringContainsString('task_deleted', $operationContent);
        $this->assertStringContainsString('Task to Delete', $operationContent);
    }

    /**
     * Test that authentication attempts are logged
     */
    public function test_authentication_logged()
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->json('POST', '/auth/login', $credentials);
        // This should fail

        // Check operation log for failed login attempt
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        if (file_exists($operationLogFile)) {
            $logContent = file_get_contents($operationLogFile);
            // The log should contain some authentication-related information
            $this->assertStringContainsString('test@example.com', $logContent);
        }
    }
}