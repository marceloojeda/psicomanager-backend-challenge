<?php

namespace App\Http\Controllers;

use App\Http\Services\UserService;
use App\Http\Validators\CreateUserValidator;
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
        return response()->json(['status' => 'success', 'data' => $this->userService->getUsers($request)], 200);
    }

    function get($userId): JsonResponse
    {
        return response()->json(['status' => 'success', 'data' => $this->userService->getUserById($userId)], 200);
    }
    
    function store(Request $request): JsonResponse
    {
        CreateUserValidator::validate($request);
        return response()->json(['status' => 'success', 'data' => $this->userService->store($request)], 200);
    }

    function delete($userId): string|JsonResponse
    {
        return  response()->json(['status' => 'success', 'message' => 'Usuário excluído com sucesso'], 200);
    }
}