<?php

namespace App\Http\Services;

use App\Http\Repositories\Interfaces\ITaskRepository;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{

    private ITaskRepository $taskRepository;

    public function __construct(ITaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function findAll(Request $request): LengthAwarePaginator
    {
        return $this->taskRepository->findAll($request);
    }

    public function findById(int $taskId): Task
    {
        return $this->taskRepository->findById($taskId);
    }
}