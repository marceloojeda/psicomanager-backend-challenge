<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    protected $taskService;

    /**
     * Injeção de dependência do TaskService no construtor.
     *
     * @param TaskService $taskService
     */
    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
        $this->taskService->setDataFromCollection(true);
    }

    /**
     * Método responsável por retornar uma lista de usuários filtrados.
     *
     * @param Request $request
     * @return JsonResponse
     */
    function index(Request $request): JsonResponse
    {
        $this->taskService->getFilteredTasks($request->all());
        return $this->taskService->getJsonResponse();
    }

    /**
     * Obter tarefa atraves do id.
     *
     * @param int $taskId    ID da tarefa
     * @return JsonResponse
     */
    function get(int $taskId): JsonResponse
    {
        $this->taskService->getTask($taskId);
        return $this->taskService->getJsonResponse();
    }
}
