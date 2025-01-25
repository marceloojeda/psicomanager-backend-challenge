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
