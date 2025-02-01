<?php

namespace App\Http\Services;

use App\Http\Repositories\Interfaces\ITaskRepository;
use Illuminate\Http\Request;

class TaskService
{

    private ITaskRepository $taskRepository;

    public function __construct(ITaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function findAll(Request $request)
    {
        if (!empty($request->input('user_id'))) {
            $results = $this->taskRepository->findByUser($request->input('user_id'));
        } else {
            $results = $this->taskRepository->findAll();

        }

        $retorno = [];
        foreach ($results as $task) {
            $retorno[] = $task->toArray();
        }
        
        return $retorno;
    }

    public function findById($taskId)
    {
        return $this->taskRepository->findById($taskId);
    }
}