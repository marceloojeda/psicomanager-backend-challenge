<?php

namespace App\Application\Services;

use App\Domain\Models\Task;
use App\Domain\Repositories\TaskRepositoryInterface;

class TaskService
{
    private $taskRepository;

    public function __construct(TaskRepositoryInterface $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function createTask($userId, $description)
    {
        $task = new Task(null, $userId, $description);
        $this->taskRepository->save($task);
    }

    public function getTasks()
    {
        return $this->taskRepository->getAll();
    }

    public function getTasksByUserId($userId)
    {
        return $this->taskRepository->findByUserId($userId);
    }

    public function deleteTask($taskId)
    {
        $task = $this->taskRepository->findById($taskId);
        $this->taskRepository->delete($task);
    }
}
