<?php

namespace App\Http\Controllers;

use App\Http\Services\TaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    function index(Request $request)
    {
        return response()->json($this->taskService->findAll($request));
    }

    function getById($taskId)
    {
        return response()->json($this->taskService->findById($taskId));
    }
}
