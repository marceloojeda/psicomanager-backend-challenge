<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Interfaces\TaskRepositoryInterface;
use App\Domain\Entities\TaskEntity;
use App\Infrastructure\Persistence\Models\Task;
use App\Infrastructure\Cache\RedisCacheService;

/**
 * Repositório para manipulação das tarefas.
 *
 * Esta classe é responsável por implementar os métodos definidos na interface TaskRepositoryInterface.
 * Ela realiza operações de acesso e manipulação de dados de tarefas no banco de dados, utilizando o modelo Task.
 */
class TaskRepository implements TaskRepositoryInterface {

    private RedisCacheService $cacheService;

    /**
     * Construtor do repositório.
     *
     * @param RedisCacheService $cacheService Serviço de cache do Redis.
     */
    public function __construct(RedisCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Obtém uma tarefa pelo seu ID.
     *
     * @param int $taskId O ID da tarefa a ser recuperada.
     * @return TaskEntity A tarefa correspondente ao ID fornecido.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Caso a tarefa não seja encontrada.
     */
    public function getTask(int $taskId): ?TaskEntity {
        $task = Task::findOrFail($taskId);

        return new TaskEntity(
            id: $task->id,
            userId: $task->user_id,
            description: $task->description,
            status: $task->status,
            createdAt: $task->created_at
        );
    }

    /**
     * Obtém uma lista paginada de tarefas, opcionalmente filtradas por usuário, com caching no Redis.
     *
     * @param array $params Parâmetros para busca e paginação.
     *
     * @return array Lista de tarefas convertidas para TaskEntity.
     */
    public function getFilteredTasks(array $params): array
    {
        $params = $this->normalizeParams($params);

        $userId = $params['user_id'];
        $page = $params['page'];
        $perPage = $params['perPage'];

        $cacheKey = "tasks_page_{$page}_perPage_{$perPage}";

        if ($cachedTasks = $this->cacheService->getWithIdentifier($cacheKey, $userId)) {
            return $cachedTasks;
        }

        $query = Task::query();
        if (!is_null($userId)) {
            $query->where('user_id', $userId);
        }

        /** @var LengthAwarePaginator $tasks */
        $tasks = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $taskEntities = $tasks->map(fn($task) => new TaskEntity(
            id: $task->id,
            userId: $task->user_id,
            description: $task->description,
            status: $task->status,
            createdAt: $task->created_at
        ))->toArray();

        $this->cacheService->setWithIdentifier($cacheKey, $taskEntities, 600, $userId);

        return $taskEntities;
    }

    /**
     * Normaliza os parâmetros de entrada, garantindo valores padrão.
     *
     * @param array $params Parâmetros recebidos para filtragem e paginação.
     *
     * @return array Parâmetros ajustados com valores padrão aplicados.
     */
    private function normalizeParams(array $params): array
    {
        return [
            'user_id' => !empty($params['user_id']) ? $params['user_id'] : null,
            'page' => !empty($params['page']) ? (int) $params['page'] : 1,
            'perPage' => !empty($params['perPage']) ? (int) $params['perPage'] : 100,
        ];
    }
}
