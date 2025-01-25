<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
    
    function store(Request $request)
    {
        $user = User::create($request->all());

        return response()->json($user);
    }

    function delete($userId)
    {
        $user = User::where('id', $userId)->firstOrFail();

        $user->delete();

        return response("Usuario excluido com sucesso", Response::HTTP_ACCEPTED);
    }
}