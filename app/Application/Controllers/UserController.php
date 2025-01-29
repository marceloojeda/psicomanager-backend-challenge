<?php

namespace App\Application\Controllers;

use Illuminate\Http\Request;
use App\Domain\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

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
        $this->userService->getFilteredUsersService($request->all());
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
        Cache::flush();
        $this->userService->getUserService($userId);
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
            $this->userService->createUserService($request->all());

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
        $this->userService->deleteUserService($userId);
        return $this->userService->getJsonResponse();
    }
}
