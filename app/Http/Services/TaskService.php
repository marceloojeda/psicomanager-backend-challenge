<?php

namespace App\Http\Services;

use App\Exceptions\ApiException;
use App\Http\Repositories\Interfaces\ITaskRepository;
use App\Models\Task;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Throwable;

class TaskService
{

    private ITaskRepository $taskRepository;

    public function __construct(ITaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    public function findAll(Request $request): LengthAwarePaginator
    {
        try {
            return $this->taskRepository->findAll($request);
        } catch (QueryException $e) {
            throw new ApiException('Erro ao buscar tarefas', 500);
        } catch (Throwable $e) {
            throw new ApiException('Erro interno', 500);
        }
    }

    public function findById(int $taskId): Task
    {
        try {
            $task = $this->taskRepository->findById($taskId);

            if (!$task) {
                throw new ApiException('Tarefa não encontrada', 404);
            }

            return $task;
        } catch (QueryException $e) {
            throw new ApiException('Erro ao buscar tarefa', 500);
        } catch (Throwable $e) {
            throw new ApiException('Erro interno', 500);
        }
    }
}