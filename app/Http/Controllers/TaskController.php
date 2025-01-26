<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'user_id' => 'integer',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!empty($request->input('user_id'))) {

            $results = Task::where(['user_id' => $request->input('user_id')])->get();
        } else {

            $results = Task::all();
        }

        return response()->json($results);
    }

    function get(int $taskId)
    {
        $task = Task::where('id', $taskId)->firstOrFail();
        return response()->json($task);
    }
}
