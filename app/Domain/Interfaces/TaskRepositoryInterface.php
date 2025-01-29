<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\TaskEntity;

interface TaskRepositoryInterface {
    public function getTask(int $taskId): ?TaskEntity;
    public function getFilteredTasks(array $filters): array;
}
