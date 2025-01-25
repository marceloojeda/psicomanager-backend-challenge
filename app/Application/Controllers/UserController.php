<?php

namespace App\Application\Controllers;

use App\Application\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        return response()->json($this->userService->getUsers());
    }

    public function store(Request $request)
    {
        $this->userService->createUser($request->input('name'), $request->input('email'));
        return response()->json(['message' => 'User created']);
    }

    public function delete($userId)
    {
        $this->userService->deleteUser($userId);
        return response()->json(['message' => 'User deleted']);
    }
}


function index()
{
    $users = User::all();

    return response()->json($users);
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
