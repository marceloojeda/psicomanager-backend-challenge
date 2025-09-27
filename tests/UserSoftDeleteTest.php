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
        $response = $this->delete("/users/{$user->id}");

        // Check response
        $response->seeStatusCode(200)
                 ->seeJsonContains(['message' => 'Usuário excluído com sucesso (soft delete).'])
                 ->seeJsonContains(['user_id' => (string)$user->id]);

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
        $response = $this->delete("/users/{$user->id}");

        // Should succeed with soft delete
        $response->seeStatusCode(200)
                 ->seeJsonContains(['message' => 'Usuário excluído com sucesso (soft delete).'])
                 ->seeJsonContains(['user_id' => (string)$user->id])
                 ->seeJsonContains(['tasks_preserved' => "Foram preservadas {$tasksCount} tarefas associadas."]);

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
        $response = $this->delete('/users/invalid');

        $response->seeStatusCode(400)
                 ->seeJsonContains(['error' => 'ID inválido. O ID deve ser um número positivo.']);
    }

    /**
     * Test soft delete with non-existent user
     */
    public function test_soft_delete_non_existent_user()
    {
        $response = $this->delete('/users/99999');

        $response->seeStatusCode(404)
                 ->seeJsonContains(['error' => 'Usuário não encontrado.']);
    }

    /**
     * Test that normal GET requests don't return soft deleted users
     */
    public function test_get_users_excludes_soft_deleted()
    {
        // Get initial count
        $initialCount = User::count();

        // Create and delete a user
        $user = User::factory()->create(['email' => 'test.softdelete@example.com']);
        $user->delete();

        // GET /users should not include soft deleted user
        $response = $this->get('/users');
        $response->seeStatusCode(200);

        $users = json_decode($response->response->getContent(), true);
        
        // Should have same count as before (soft deleted user not included)
        // Users are returned directly when no pagination is used
        $this->assertEquals($initialCount, count($users));

        // Verify the soft deleted user is not in the results
        $userIds = array_column($users, 'id');
        $this->assertNotContains($user->id, $userIds);
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
            $response = $this->delete("/users/{$user->id}");
            $response->seeStatusCode(200);
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
        $response = $this->patch("/users/{$user->id}/restore");
        
        $response->seeStatusCode(200)
                 ->seeJsonContains(['message' => 'Usuário restaurado com sucesso.'])
                 ->seeJsonContains(['user_id' => (string)$user->id])
                 ->seeJsonContains(['tasks_associated' => 'Usuário possui 2 tarefas associadas.']);
        
        // User should be restored
        $this->assertTrue(User::where('id', $user->id)->exists());
        $this->assertFalse(User::withTrashed()->find($user->id)->trashed());
    }

    /**
     * Test restore non-existent user
     */
    public function test_restore_non_existent_user()
    {
        $response = $this->patch("/users/99999/restore");
        
        $response->seeStatusCode(404)
                 ->seeJsonContains(['error' => 'Usuário não encontrado.'])
                 ->seeJsonContains(['message' => 'Usuário com ID 99999 não existe ou não foi soft deleted.']);
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
        $response = $this->patch("/users/{$user->id}/restore");
        
        $response->seeStatusCode(409)
                 ->seeJsonContains(['error' => 'Usuário não foi excluído.'])
                 ->seeJsonContains(['message' => "Usuário com ID {$user->id} está ativo e não precisa ser restaurado."]);
    }

    /**
     * Test restore with invalid user ID
     */
    public function test_restore_invalid_user_id()
    {
        $response = $this->patch("/users/invalid/restore");
        
        $response->seeStatusCode(400)
                 ->seeJsonContains(['error' => 'ID do usuário inválido.'])
                 ->seeJsonContains(['message' => 'O ID deve ser um número inteiro positivo.']);
    }
}