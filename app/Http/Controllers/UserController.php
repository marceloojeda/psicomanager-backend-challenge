<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    protected $userService;

    /**
     * Injeção de dependência do UserService no construtor.
     *
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Método responsável por retornar uma lista de usuários filtrados.
     *
     * @param Request $request
     * @return JsonResponse
     */
    function index(Request $request): JsonResponse
    {
        //$this->userService->setDataFromCollection(false);
        $data = $this->userService->getFilteredUsers($request->all());
        return response()->json($data, $data['status']);
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
