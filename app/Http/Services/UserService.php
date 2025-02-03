<?php

namespace App\Http\Services;

use App\Exceptions\ApiException;
use App\Http\Repositories\Interfaces\ITaskRepository;
use App\Http\Repositories\Interfaces\IUserRepository;
use App\Http\Resources\UserResource;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class UserService
{
    private IUserRepository $userRepository;
    private ITaskRepository $taskRepository;
    private LogService $logService;

    public function __construct(
        IUserRepository $userRepository, 
        ITaskRepository $taskRepository,
        LogService $logService
    )
    {
        $this->userRepository = $userRepository;
        $this->taskRepository = $taskRepository;
        $this->logService = $logService;
    }

    public function getUsers(Request $request): AnonymousResourceCollection
    {
        $users = $request->has('name') || $request->has('id') 
        ? $this->userRepository->findByFilter($request) 
        : $this->userRepository->findAll();

        if ($users->isEmpty()) {
            throw new ApiException('Nenhum usuário encontrado', 404);
        }

        return UserResource::collection($users);
    }

    public function getUserById(int $userId): UserResource
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new ApiException('Usuário não encontrado', 404);
        }

        return new UserResource($user);
    }

    public function store(Request $request): UserResource
    {
        try {
            $user = $this->userRepository->persist($request);

            $this->logService->logInfo('Usuário criado com sucesso', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'email' => $user->email,
            ]);

            return new UserResource($user);
        } catch (ValidationException $e) {
            throw new ApiException('Erro de validação', Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (QueryException $e) {
            throw new ApiException('Erro ao criar usuário', Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Throwable $e) {
            throw new ApiException($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function delete(int $userId): void
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new ApiException('Usuário não encontrado', 404);
        }

        $admin = $this->userRepository->findUserIsAdmin();

        if (!$admin) {
            throw new ApiException('Nenhum administrador encontrado', 404);
        }

        $transferredTasks = $this->taskRepository->transferTasks($userId, $admin->id);

        if ($transferredTasks === 0) {
            throw new ApiException('Erro ao transferir tarefas', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->logService->logInfo('Tarefas transferidas com sucesso', [
            'user_id' => $userId,
            'admin_id' => $admin->id,
            'transferred_tasks' => $transferredTasks,
        ]);
        
        $this->userRepository->delete($userId);

        $this->logService->logWarning('Usuário excluído com sucesso', [
            'user_id' => $userId,
            'user_name' => $user->name,
        ]);
    }
}