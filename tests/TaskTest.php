<?php

use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class TaskTest extends TestCase
{
    public function test_get_all_tasks()
    {
        $user = User::factory()->create();

        Task::factory()->count(1200)->create(['user_id' => $user->id]);

        $this->actingAs($user, 'api')
            ->get('/tasks')
            ->seeStatusCode(200)
            ->seeJsonStructure([
                '*' => [
                    'id',
                    'user_id',
                    'description',
                    'status'
                ]
            ]);
    }

    public function test_get_task_by_user()
    {
        $user = User::factory()->create();

        Task::factory()->count(10)->create(['user_id' => $user->id]);

        $this->actingAs($user, 'api')
            ->get('/tasks?user_id=' . $user->id)
            ->seeStatusCode(200)
            ->seeJsonStructure([
                '*' => [
                    'id',
                    'user_id',
                    'description',
                    'status'
                ]
            ]);
    }

    public function test_get_task_by_id()
    {
        $user = User::factory()->create();
        $task = Task::factory()->create();

        $this->actingAs($user, 'api')
            ->get("tasks/{$task->id}")
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'id',
                'user_id',
                'description',
                'status'
            ]);
    }
}