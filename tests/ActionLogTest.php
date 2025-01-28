<?php

namespace Tests;

use App\Models\Task;
use App\Models\ActionLog;


class ActionLogTest extends TestCase
{
    public function test_users_action_log()
    {
        $userCreateData = [
            'name' => 'Test User Action Log',
            'email' => 'actionlogusercreate@test.com',
            'password' => 'abc123',
        ];
        $this->post('/users', $userCreateData);

        $this->assertResponseStatus(200);
        $createdUserId = $this->response->json()['id'];

        $logCreate = ActionLog::where('action', 'create')
            ->where('table', 'users')
            ->whereJsonContains('data', ['id' => $createdUserId])->get();
        $this->assertNotEmpty($logCreate);

        $this->delete('/users/' . $createdUserId);
        $logDelete = ActionLog::where('action', 'delete')
            ->where('table', 'users')
            ->whereJsonContains('data', ['id' => $createdUserId])->get();
        $this->assertNotEmpty($logDelete);
    }

    public function test_tasks_action_log()
    {
        $newTask = Task::create([
            'user_id' => 50,
            'description' => 'Testando action log',
            'status' => 'pendente',
        ]);
        $createdTaskId = $newTask->id;
        $logCreate = ActionLog::where('action', 'create')
            ->where('table', 'tasks')
            ->whereJsonContains('data', ['id' => $createdTaskId])->get();
        $this->assertNotEmpty($logCreate);

        $newTask->status = 'em progresso';
        $newTask->save();
        $logUpdate = ActionLog::where('action', 'update')
            ->where('table', 'tasks')
            ->whereJsonContains('data', ['id' => $createdTaskId])->get();
        $this->assertNotEmpty($logUpdate);

        $newTask->delete();
        $logDelete = ActionLog::where('action', 'delete')
            ->where('table', 'tasks')
            ->whereJsonContains('data', ['id' => $createdTaskId])->get();
        $this->assertNotEmpty($logDelete);
    }
}
