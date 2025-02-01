<?php

namespace App\Http\Controllers;

use App\Http\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    function index(Request $request)
    {
        return response()->json($this->userService->getUsers($request));
    }

    function get($userId)
    {
        return response()->json($this->userService->getUserById($userId));
    }
    
    function store(Request $request)
    {
        return response()->json($this->userService->store($request));
    }

    function delete($userId)
    {
        $this->userService->delete($userId);
        return response("Usuario excluido com sucesso", Response::HTTP_ACCEPTED);
    }
}