<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

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
            $tasks = Cache::remember('tasks_user_' . $request->input('user_id'), 60, function () use ($request) {
                return Task::when(!empty($request->input('user_id')), function ($query) use ($request) {
                    return $query->where('user_id', $request->input('user_id'));
                })->select('id', 'user_id', 'description', 'status')->get();
            });
        } else {
            $tasks = Cache::remember('tasks_all', 60, function () {
                return Task::select('id', 'user_id', 'description', 'status')->get();
            });
        }

        return response()->json($tasks);
    }

    function get($taskId)
    {
        try {
            $task = Task::select('id', 'user_id', 'description', 'status')->where('id', $taskId)->firstOrFail();
            return response()->json($task, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Task não encontrada'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Erro ao buscar task', 'error' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
