<?php

namespace App\Http\Controllers;

use App\Http\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    function index(Request $request): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->taskService->findAll($request)], 200);
    }

    function getById($taskId): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->taskService->findById($taskId)], 200);
    }
}
