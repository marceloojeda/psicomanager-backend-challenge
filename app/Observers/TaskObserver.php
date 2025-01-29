<?php

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Facades\Log;

class TaskObserver
{
    public function created(Task $task)
    {
        Log::info('Task created: ', ['task_id' => $task->id]);
    }

    public function updated(Task $task)
    {
        Log::info('Task updated: ', ['task_id' => $task->id]);
    }

    public function deleted(Task $task)
    {
        Log::warning('Task deleted: ', ['task_id' => $task->id]);
    }
}
