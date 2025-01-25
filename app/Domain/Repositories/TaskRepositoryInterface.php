<?php

namespace App\Domain\Repositories;

use App\Domain\Models\Task;

interface TaskRepositoryInterface
{
    public function findById($id): Task;
    public function findByUserId($userId): array;
    public function getAll(): array;
    public function save(Task $task): void;
    public function delete(Task $task): void;
}
