<?php

namespace App\Http\Repositories\Interfaces;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

interface ITaskRepository
{
    public function findAll(Request $request): LengthAwarePaginator;
    public function transferTasks(int $userId, int $adminId): array;
    public function findById(int $taskId): Task;
}