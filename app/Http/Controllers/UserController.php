<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;


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
        $this->userService->setDataFromCollection(true);
    }

    /**
     * Método responsável por retornar uma lista de usuários filtrados.
     *
     * @param Request $request
     * @return JsonResponse
     */
    function index(Request $request): JsonResponse
    {
        Cache::flush();
        $data = $this->userService->getFilteredUsers($request->all());
        return response()->json($data, $data['status']);
    }

    /**
     * Obter usuário atraves do id.
     *
     * @param int $userId    Classe model User
     * @return JsonResponse
     */
    function get(int $userId): JsonResponse
    {
        $data = [];

        try {
            $item = User::findOrFail($userId);
            $data = $this->userService->getUser($item);
            return response()->json($data, $data['status']);
        } catch (ModelNotFoundException $e) {
            $this->userService->setStatus(Response::HTTP_NOT_FOUND);
            $this->userService->setMessage('Usuário não encontrado.');
            $this->userService->setError($e->getMessage());
        }

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
