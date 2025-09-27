<?php

namespace Tests;

use App\Models\User;
use App\Models\Task;
use Tests\TestCase;

class UserSoftDeleteTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Não limpar dados existentes - usar dados das seeds para testes realistas
    }

    /**
     * Test that user is soft deleted instead of hard deleted
     */
    public function test_user_soft_delete_success()
    {
        // Find a user without tasks from existing data
        $user = User::doesntHave('tasks')->first();
        
        if (!$user) {
            // Create one if none exists without tasks
            $user = User::factory()->create([
                'name' => 'Test Soft Delete User',
                'email' => 'softdelete' . time() . '@test.com'
            ]);
        }

        // Ensure user exists
        $this->assertTrue(User::where('id', $user->id)->exists());

        // Delete user via API
        $response = $this->authenticatedDelete("/users/{$user->id}");

        // Check response
        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Usuário excluído com sucesso (soft delete).', $responseData['message']);
        $this->assertEquals((string)$user->id, $responseData['user_id']);
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Usuário excluído com sucesso (soft delete).', $responseData['message']);
        $this->assertEquals((string)$user->id, $responseData['user_id']);

        // User should not appear in normal queries (soft deleted)
        $this->assertFalse(User::where('id', $user->id)->exists());

        // But should exist when including trashed
        $this->assertTrue(User::withTrashed()->where('id', $user->id)->exists());

        // Check deleted_at is set
        $deletedUser = User::withTrashed()->find($user->id);
        $this->assertNotNull($deletedUser->deleted_at);
    }

    /**
     * Test that user with tasks can be soft deleted
     */
    public function test_user_with_tasks_soft_delete_success()
    {
        // Use existing user with tasks from seeds
        $user = User::whereHas('tasks')->first();
        
        if (!$user) {
            // Create one if none exists with tasks
            $user = User::factory()->create([
                'name' => 'Test User With Tasks',
                'email' => 'withtasks' . time() . '@test.com'
            ]);
            Task::factory()->count(3)->create(['user_id' => $user->id]);
        }
        
        $tasksCount = $user->tasks()->count();
        $this->assertGreaterThan(0, $tasksCount, 'User should have tasks for this test');

        // Delete user via API
        $response = $this->authenticatedDelete("/users/{$user->id}");

        // Should succeed with soft delete
        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Usuário excluído com sucesso (soft delete).', $responseData['message']);
        $this->assertEquals((string)$user->id, $responseData['user_id']);
        $this->assertEquals("Foram preservadas {$tasksCount} tarefas associadas.", $responseData['tasks_preserved']);

        // User should be soft deleted
        $this->assertFalse(User::where('id', $user->id)->exists());
        $this->assertTrue(User::withTrashed()->where('id', $user->id)->exists());

        // Tasks should still exist and reference the soft deleted user
        $this->assertEquals($tasksCount, Task::where('user_id', $user->id)->count());
    }

    /**
     * Test that tasks can still access soft deleted user through relationship
     */
    public function test_task_relationship_with_soft_deleted_user()
    {
        // Use existing user with tasks from seeds
        $user = User::whereHas('tasks')->first();
        
        if (!$user) {
            // Create one if none exists
            $user = User::factory()->create([
                'name' => 'User for Relationship Test',
                'email' => 'relationship' . time() . '@test.com'
            ]);
            Task::factory()->create(['user_id' => $user->id]);
        }
        
        $task = $user->tasks()->first();
        $this->assertNotNull($task, 'User should have at least one task');
        
        // Delete user (soft delete)
        $user->delete();

        // Refresh task from database
        $task = Task::find($task->id);

        // Task should still be able to access user through withTrashed relationship
        $this->assertNotNull($task->user);
        $this->assertEquals($user->id, $task->user->id);
        $this->assertNotNull($task->user->deleted_at);
    }

    /**
     * Test soft delete with invalid user ID
     */
    public function test_soft_delete_invalid_user_id()
    {
        $response = $this->authenticatedDelete('/users/invalid');

        $this->assertEquals(400, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('ID inválido. O ID deve ser um número positivo.', $responseData['error']);
    }

    /**
     * Test soft delete with non-existent user
     */
    public function test_soft_delete_non_existent_user()
    {
        $response = $this->authenticatedDelete('/users/99999');

        $this->assertEquals(404, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Usuário não encontrado.', $responseData['error']);
    }

    /**
     * Test that normal GET requests don't return soft deleted users
     */
    public function test_get_users_excludes_soft_deleted()
    {
        // Create and delete a user first
        $user = User::factory()->create(['email' => 'test.softdelete@example.com']);
        $createdUserId = $user->id;
        $user->delete();

        // GET /users should not include soft deleted user
        $response = $this->authenticatedGet('/users');
        $this->assertEquals(200, $response->response->getStatusCode());

        $users = json_decode($response->response->getContent(), true);
        
        // Verify the soft deleted user is not in the results
        $userIds = array_column($users, 'id');
        $this->assertNotContains($createdUserId, $userIds);

        // Verify that the user exists in database with trashed
        $this->assertTrue(User::withTrashed()->where('id', $createdUserId)->exists());
        $this->assertFalse(User::where('id', $createdUserId)->exists());
        
        // Verify all returned users are not soft deleted
        foreach ($users as $userData) {
            $dbUser = User::find($userData['id']);
            $this->assertNotNull($dbUser, "User {$userData['id']} should exist in active users");
            $this->assertNull($dbUser->deleted_at, "User {$userData['id']} should not be soft deleted");
        }
    }

    /**
     * Test that restore functionality works (bonus feature)
     */
    public function test_user_can_be_restored()
    {
        $user = User::factory()->create(['email' => 'restore.test@example.com']);
        
        // Soft delete
        $user->delete();
        $this->assertTrue($user->trashed());

        // Restore
        $user->restore();
        $this->assertFalse($user->trashed());
        $this->assertNull($user->deleted_at);

        // Should appear in normal queries again
        $this->assertTrue(User::where('id', $user->id)->exists());
    }

    /**
     * Test multiple soft deletes don't cause issues
     */
    public function test_multiple_soft_deletes()
    {
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $response = $this->authenticatedDelete("/users/{$user->id}");
            $this->assertEquals(200, $response->response->getStatusCode());
        }

        // All should be soft deleted
        foreach ($users as $user) {
            $this->assertFalse(User::where('id', $user->id)->exists());
            $this->assertTrue(User::withTrashed()->where('id', $user->id)->exists());
        }
    }

    /**
     * Test successful user restoration
     */
    public function test_user_restore_success()
    {
        $user = User::factory()->create([
            'name' => 'Test Restore User',
            'email' => 'restore@test.com'
        ]);
        
        // Create tasks for the user
        Task::factory()->count(2)->create(['user_id' => $user->id]);
        
        // Soft delete the user first
        $user->delete();
        $this->assertTrue($user->trashed());
        
        // Restore via API
        $response = $this->authenticatedPatch("/users/{$user->id}/restore");
        
        $this->assertEquals(200, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Usuário restaurado com sucesso.', $responseData['message']);
        $this->assertEquals((string)$user->id, $responseData['user_id']);
        $this->assertEquals('Usuário possui 2 tarefas associadas.', $responseData['tasks_associated']);
        
        // User should be restored
        $this->assertTrue(User::where('id', $user->id)->exists());
        $this->assertFalse(User::withTrashed()->find($user->id)->trashed());
    }

    /**
     * Test restore non-existent user
     */
    public function test_restore_non_existent_user()
    {
        $response = $this->authenticatedPatch("/users/99999/restore");
        
        $this->assertEquals(404, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Usuário não encontrado.', $responseData['error']);
        $this->assertEquals('Usuário com ID 99999 não existe ou não foi soft deleted.', $responseData['message']);
    }

    /**
     * Test restore active user (not soft deleted)
     */
    public function test_restore_active_user()
    {
        $user = User::factory()->create([
            'name' => 'Active User',
            'email' => 'active@test.com'
        ]);
        
        // Try to restore active user
        $response = $this->authenticatedPatch("/users/{$user->id}/restore");
        
        $this->assertEquals(409, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('Usuário não foi excluído.', $responseData['error']);
        $this->assertEquals("Usuário com ID {$user->id} está ativo e não precisa ser restaurado.", $responseData['message']);
    }

    /**
     * Test restore with invalid user ID
     */
    public function test_restore_invalid_user_id()
    {
        $response = $this->authenticatedPatch("/users/invalid/restore");
        
        $this->assertEquals(400, $response->response->getStatusCode());
        
        $responseData = json_decode($response->response->getContent(), true);
        $this->assertEquals('ID do usuário inválido.', $responseData['error']);
        $this->assertEquals('O ID deve ser um número inteiro positivo.', $responseData['message']);
    }
}