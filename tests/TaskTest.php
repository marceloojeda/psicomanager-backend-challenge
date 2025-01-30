<?php

namespace Tests;
use App\Infrastructure\Persistence\Models\Task;

class TaskTest extends TestCase
{
    /**
     * Testar a lista de tarefas na rota /tasks
     */
    public function test_that_endpoint_tasks_returns_a_successful_response(): void
    {
        $this->get("tasks", []);

        $this->seeStatusCode(200);
        $this->seeJsonStructure([
            'message',
            'data' => [
                '*' =>
                    [
                        'id',
                        'userId',
                        'description',
                        'status',
                        'createdAt'
                    ]
                ],
            'error',
            'status'
        ]);
    }

    /**
     * Testar a rota de pegar tarefa na rota /tasks/taskId
     */
    public function test_that_endpoint_get_a_task_returns_a_successful_response(): void
    {
        $task = Task::first();

        $response = $this->get("tasks/{$task->id}");

        $response->seeStatusCode(200);

        $response->seeJsonStructure([
            'message',
            'data' => [
                'id',
                'userId',
                'description',
                'status',
                'createdAt'
            ],
            'error',
            'status'
        ]);
    }
}
