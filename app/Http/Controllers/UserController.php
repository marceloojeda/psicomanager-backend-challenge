<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    function index(Request $request)
    {
        $users = User::select();

        $validation = Validator::make($request->all(), [
            'id' => 'integer',
            'name' => 'min:3|max:25'
        ]);
        if ($validation->fails()) {
            return response($validation->errors(), 422);
        }

        if (!empty($request->input('id'))) {
            $users = $users->where(['id' => $request->input('id')]);
        }
        if (!empty($request->input('name'))) {
            $users = $users->where('name', 'like', '%'.$request->input('name').'%');
        }

        return response()->json($users->get());
    }
    
    function get(int $userId)
    {
        return response()->json(User::find($userId));
    }

    function store(Request $request)
    {
        $userCreateData = $request->all();
        // validation
        $validator = Validator::make($userCreateData, [
            'name' => 'bail|required|min:3|max:255',
            'email' => 'required|unique:users,email|max:255',
            'password' => 'required|min:6'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $userCreateData['password'] = Hash::make($userCreateData['password']);
        $user = User::create($userCreateData);

        return response()->json($user);
    }

    function delete(int $userId)
    {
        try {
            $user = User::where('id', $userId)->firstOrFail();
            $user->delete();
            Task::where('user_id', $user->id)->delete();
        } catch (\Throwable $th) {
            Log::critical(
                'Erro ao excluir usuário: ' . $th->getMessage() .
                ' :: (' . $userId . ')'
            );
            return response()->json([
                'error' => true,
                'message' => 'Erro ao excluir usuário',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario excluido com sucesso'
        ], Response::HTTP_ACCEPTED);
    }
}