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
        $this->userService->getFilteredUsers($request->all());
        return $this->userService->getJsonResponse();
    }

    /**
     * Obter usuário atraves do id.
     *
     * @param int $userId    Classe model User
     * @return JsonResponse
     */
    function get(int $userId): JsonResponse
    {
        try {
            $item = User::findOrFail($userId);
            $this->userService->getUser($item);
        } catch (ModelNotFoundException $e) {
            $this->userService->setStatus(Response::HTTP_NOT_FOUND);
            $this->userService->setMessage('Usuário não encontrado.');
            $this->userService->setError($e->getMessage());
        }

        return $this->userService->getJsonResponse();
    }

    /**
     * Cadastrar novo usuário.
     *
     * @param Request $request Dados usuario para salvar.
     * @return JsonResponse
     */
    function store(Request $request): JsonResponse
    {
        $passed = $this->userService->validateUser($request);

        if ($passed === true)
            $this->userService->createUser($request->all());

        return $this->userService->getJsonResponse();
    }

    /**
     * Excluir usuário.
     *
     * @param int $userId   ID do usuario para excluir.
     * @return JsonResponse
     */
    function delete(int $userId): JsonResponse
    {
        $this->userService->deleteUser($userId);
        return $this->userService->getJsonResponse();
    }
}
