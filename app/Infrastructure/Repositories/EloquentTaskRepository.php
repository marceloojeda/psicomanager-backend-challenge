<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Models\Task;
use App\Domain\Repositories\TaskRepositoryInterface;
use App\Models\Task as EloquentTask;

class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function findById($id): Task
    {
        $eloquentTask = EloquentTask::findOrFail($id);
        return new Task($eloquentTask->id, $eloquentTask->user_id, $eloquentTask->description);
    }

    public function findByUserId($userId): array
    {
        $eloquentTasks = EloquentTask::where('user_id', $userId)->get();
        return $eloquentTasks->map(function ($task) {
            return new Task($task->id, $task->user_id, $task->description);
        })->toArray();
    }

    public function getAll(): array
    {
        $eloquentTasks = EloquentTask::all();
        return $eloquentTasks->map(function ($task) {
            $item = new Task($task->id, $task->user_id, $task->description);
            return $item;
        })->toArray();
    }

    public function save(Task $task): void
    {
        EloquentTask::create([
            'user_id' => $task->getUserId(),
            'description' => $task->getDescription(),
        ]);
    }

    public function delete(Task $task): void
    {
        EloquentTask::destroy($task->getId());
    }
}
