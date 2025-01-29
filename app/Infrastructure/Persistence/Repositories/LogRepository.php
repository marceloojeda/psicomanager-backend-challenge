<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Interfaces\LogRepositoryInterface;
use App\Domain\Entities\LogEntity;
use App\Infrastructure\Persistence\Models\Log;
use App\Infrastructure\Cache\RedisCacheService;

/**
 * Repositório para manipulação das tarefas.
 *
 * Esta classe é responsável por implementar os métodos definidos na interface TaskRepositoryInterface.
 * Ela realiza operações de acesso e manipulação de dados de tarefas no banco de dados, utilizando o modelo Task.
 */
class LogRepository implements LogRepositoryInterface {

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
     * Obtém uma lista paginada de logs, opcionalmente filtradas, com caching no Redis.
     *
     * @param array $params Parâmetros para busca e paginação.
     *
     * @return array Lista de logs.
     */
    public function getFilteredLogs(array $params): array
    {
        $params = $this->normalizeParams($params);

        $userId = $params['user_id'];
        $page = $params['page'];
        $perPage = $params['perPage'];

        $cacheKey = "tasks_page_{$page}_perPage_{$perPage}";

        if ($cachedTasks = $this->cacheService->getWithIdentifier($cacheKey, $userId)) {
            return $cachedTasks;
        }

        $query = Log::query();
        if (!is_null($userId)) {
            $query->where('user_id', $userId);
        }

        /** @var LengthAwarePaginator $tasks */
        $tasks = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $taskEntities = $tasks->map(fn($task) => new LogEntity(
            id: $task->id,
            level: $task->level,
            message: $task->message,
            context: $task->context,
            userId: $task->user_id ?? '',
            ipAddress: $task->ip_address ?? '',
            requestId: $task->request_id ?? '',
            createdAt: $task->created_at
        ))->toArray();

        $this->cacheService->setWithIdentifier($cacheKey, $taskEntities, 0, $userId);

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
