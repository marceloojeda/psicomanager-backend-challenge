<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    function index(Request $request)
    {
        $filters = [
            'id' => $request->query('id'),
            'name' => $request->query('name'),
        ];

        $users = $this->userService->getFilteredUsers($filters);

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
}
