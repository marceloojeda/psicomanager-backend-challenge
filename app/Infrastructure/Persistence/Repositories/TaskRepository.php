<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Interfaces\TaskRepositoryInterface;
use App\Domain\Entities\TaskEntity;
use App\Infrastructure\Persistence\Models\Task;

class TaskRepository implements TaskRepositoryInterface {

    public function getTask(int $taskId): ?TaskEntity {
        $task = Task::findOrFail($taskId);

        return new TaskEntity(
            id: $task->id,
            userId: $task->user_id,
            description: $task->description,
            status: $task->status,
            createdAt: $task->created_at
        );
    }

    public function getFilteredTasks(array $filters): array {

        $object = Task::when(!empty($filters['user_id']), function ($query) use ($filters) {
            return $query->where('user_id', $filters['user_id']);
        })
        ->get();

        return $object->map(fn($task) => new TaskEntity(
            id: $task->id,
            userId: $task->user_id,
            description: $task->description,
            status: $task->status,
            createdAt: $task->created_at
        ))->toArray();
    }
}

