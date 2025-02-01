<?php

namespace App\Http\Services;

use App\Http\Repositories\Interfaces\ITaskRepository;
use App\Http\Repositories\Interfaces\IUserRepository;
use App\Http\Validators\CreateUserValidator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserService
{
    private IUserRepository $userRepository;
    private ITaskRepository $taskRepository;

    public function __construct(IUserRepository $userRepository, ITaskRepository $taskRepository)
    {
        $this->userRepository = $userRepository;
        $this->taskRepository = $taskRepository;
    }

    public function getUsers(Request $request)
    {
        if ($request->has('name') || $request->has('id')) {
            return $this->userRepository->findByFilter($request);
        }

        return response()->json($this->userRepository->findAll());
    }

    public function getUserById($userId)
    {
        return response()->json($this->userRepository->findById($userId));
    }

    public function store(Request $request)
    {
        CreateUserValidator::validate($request);
        return response()->json($this->userRepository->persist($request));
    }

    public function delete($userId)
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            return response()->json(['error' => 'Usuário não encontrado'], 404);
        }

        $admin = $this->userRepository->findUserIsAdmin();

        if (!$admin) {
            return response()->json(['error' => 'Nenhum usuário administrador encontrado para transferir as tarefas.'], 400);
        }

        $this->taskRepository->transferTasks($userId, $admin->id);
        $this->userRepository->delete($userId);
        return response("Usuario excluido com sucesso", Response::HTTP_ACCEPTED);
    }
}