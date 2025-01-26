<?php

namespace Tests;

use App\Models\Task;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

class TaskTest extends TestCase
{
    public function test_tasks_get_by_user_id()
    {
        $this->call('GET', '/tasks', ['user_id' => 'abc123']);
        $this->assertResponseStatus(422);

        $this->call('GET', '/tasks', ['user_id' => 50]);
        $this->assertGreaterThanOrEqual(1500, count($this->response->json()));
    }

    public function test_get_task_by_id()
    {
        $this->get('/tasks/a100');
        $this->assertResponseStatus(404);

        $this->get('/tasks/100');
        $this->assertResponseOK();
        $this->seeJson(['id' => 100]);
    }
}
