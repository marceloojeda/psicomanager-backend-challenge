<?php

namespace App\Application\Controllers;

use App\Application\Services\TaskService;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Http\Controllers\Controller;

class TaskController extends Controller
{
    private $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    function index0(Request $request)
    {
        if (!empty($request->input('user_id'))) {

            $results = Task::where(['user_id' => $request->input('user_id')])->get();
        } else {

            $results = Task::all();
        }

        $retorno = [];
        foreach ($results as $task) {

            $retorno[] = $task->toArray();
        }

        return response()->json($retorno);
    }

    function get($taskId)
    {
        $task = Task::where('id', $taskId)->firstOrFail();
        return response()->json($task);
    }

    public function index(Request $request)
    {
        $userId = $request->input('user_id');
        if ($userId) {
            return response()->json($this->taskService->getTasksByUserId($userId));
        } else {
            return response()->json($this->taskService->getTasks());
        }
    }
}
