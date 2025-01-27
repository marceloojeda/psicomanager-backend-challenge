<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    function index(Request $request)
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
}
