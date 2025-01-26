<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    function index(Request $request)
    {
        $users = User::select();

        if (!empty($request->input('id'))) {
            $users = $users->where(['id' => $request->input('id')]);
        }
        if (!empty($request->input('name'))) {
            $users = $users->where(['name' => $request->input('name')]);
        }

        return response()->json($users->get());
    }
    
    function get($userId)
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

    function delete($userId)
    {
        $user = User::where('id', $userId)->firstOrFail();

        $user->delete();

        return response("Usuario excluido com sucesso", Response::HTTP_ACCEPTED);
    }
}