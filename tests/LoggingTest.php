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
        $uniqueEmail = 'logging-test' . time() . '@example.com';
        $userData = [
            'name' => 'Logging Test User',
            'email' => $uniqueEmail,
            'password' => 'password123'
        ];

        // Create user
        $response = $this->post('/users', $userData);
        $response->seeStatusCode(201);

        // Check if operation log file exists
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        $this->assertTrue(file_exists($operationLogFile), 'Operation log file should exist');

        // Check log content
        $logContent = file_get_contents($operationLogFile);
        $this->assertStringContainsString('user_created', $logContent);
        $this->assertStringContainsString('logging-test@example.com', $logContent);
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

        // Delete user
        $response = $this->delete("/users/{$user->id}");
        $response->seeStatusCode(200);

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

        // Restore user
        $response = $this->patch("/users/{$user->id}/restore");
        $response->seeStatusCode(200);

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
        // Create a test user
        $user = User::factory()->create([
            'email' => 'logging-test-tasks@example.com'
        ]);

        // Query tasks for user
        $response = $this->get("/tasks?user_id={$user->id}");
        $response->seeStatusCode(200);

        // Check operation log
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        $logContent = file_get_contents($operationLogFile);
        $this->assertStringContainsString('tasks_queried_by_user', $logContent);
        $this->assertStringContainsString("\"user_id\":\"{$user->id}\"", $logContent);
    }

    /**
     * Test log structure and required fields
     */
    public function test_log_structure()
    {
        // Create user to generate a log entry
        $uniqueEmail = 'logging-test-structure' . time() . '@example.com';
        $userData = [
            'name' => 'Structure Test User',
            'email' => $uniqueEmail,
            'password' => 'password123'
        ];

        $response = $this->post('/users', $userData);
        $response->seeStatusCode(201);

        // Read and parse the log
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        $logContent = file_get_contents($operationLogFile);
        $logLines = array_filter(explode("\n", $logContent));
        $latestLog = end($logLines);

        $logEntry = json_decode($latestLog, true);
        
        // Verify log structure
        $this->assertArrayHasKey('message', $logEntry);
        $this->assertArrayHasKey('context', $logEntry);
        $this->assertArrayHasKey('level', $logEntry);
        $this->assertArrayHasKey('datetime', $logEntry);
        
        // Verify context structure
        $context = $logEntry['context'];
        $this->assertArrayHasKey('timestamp', $context);
        $this->assertArrayHasKey('operation', $context);
        $this->assertArrayHasKey('ip_address', $context);
        $this->assertArrayHasKey('user_agent', $context);
        $this->assertArrayHasKey('data', $context);
    }

    /**
     * Test error logging
     */
    public function test_error_logging()
    {
        // Try to create user with invalid data
        $response = $this->post('/users', [
            'name' => 'Test',
            'email' => 'invalid-email',
            'password' => '123' // Too short
        ]);

        $response->seeStatusCode(422);

        // Error should still be logged in operations
        $operationLogFile = storage_path('logs/operations-' . Carbon::now()->format('Y-m-d') . '.log');
        $this->assertTrue(file_exists($operationLogFile));
    }
}