<?php

namespace App\Http\Controllers;

use App\Http\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    function index(Request $request): JsonResponse
    {
        return response()->json($this->userService->getUsers($request));
    }

    function get($userId): JsonResponse
    {
        return response()->json($this->userService->getUserById($userId));
    }
    
    function store(Request $request): JsonResponse
    {
        return response()->json($this->userService->store($request));
    }

    function delete($userId): string|JsonResponse
    {
        return $this->userService->delete($userId);
    }
}