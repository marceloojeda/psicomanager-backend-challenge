<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TaskCacheService
{
    /**
     * TTL padrão para cache (em segundos)
     * 15 minutos para dados de tarefas
     */
    const DEFAULT_TTL = 900;
    
    /**
     * TTL para listas paginadas (em segundos)
     * 5 minutos para listas que mudam mais frequentemente
     */
    const LIST_TTL = 300;

    /**
     * Busca tarefas por user_id com cache
     */
    public function getTasksByUserId(int $userId, int $page = 1, int $perPage = 50): array
    {
        $cacheKey = "tasks:user:{$userId}:page:{$page}:per_page:{$perPage}";
        
        return Cache::remember($cacheKey, self::LIST_TTL, function () use ($userId, $page, $perPage) {
            Log::info("Cache MISS - Buscando tasks do usuário {$userId} no banco", [
                'user_id' => $userId,
                'page' => $page,
                'per_page' => $perPage,
                'cache_key' => "tasks:user:{$userId}:page:{$page}:per_page:{$perPage}"
            ]);

            $query = Task::with('user')->where('user_id', $userId);
            $tasks = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
            
            return [
                'data' => $tasks->items(),
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage()
                ],
                'cached_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Busca todas as tarefas com cache
     */
    public function getAllTasks(int $page = 1, int $perPage = 50): array
    {
        $cacheKey = "tasks:all:page:{$page}:per_page:{$perPage}";
        
        return Cache::remember($cacheKey, self::LIST_TTL, function () use ($page, $perPage) {
            Log::info("Cache MISS - Buscando todas as tasks no banco", [
                'page' => $page,
                'per_page' => $perPage,
                'cache_key' => "tasks:all:page:{$page}:per_page:{$perPage}"
            ]);

            $query = Task::with('user');
            $tasks = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
            
            return [
                'data' => $tasks->items(),
                'pagination' => [
                    'current_page' => $tasks->currentPage(),
                    'per_page' => $tasks->perPage(),
                    'total' => $tasks->total(),
                    'last_page' => $tasks->lastPage()
                ],
                'cached_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Busca uma tarefa específica por ID com cache
     */
    public function getTaskById(int $taskId): ?array
    {
        $cacheKey = "task:{$taskId}";
        
        return Cache::remember($cacheKey, self::DEFAULT_TTL, function () use ($taskId) {
            Log::info("Cache MISS - Buscando task {$taskId} no banco", [
                'task_id' => $taskId,
                'cache_key' => "task:{$taskId}"
            ]);

            $task = Task::with('user')->find($taskId);
            
            if (!$task) {
                return null;
            }
            
            return [
                'data' => $task,
                'cached_at' => now()->toISOString()
            ];
        });
    }

    /**
     * Invalida cache relacionado a um usuário
     * Chamado quando tarefas do usuário são criadas/atualizadas/excluídas
     */
    public function invalidateUserCache(int $userId): void
    {
        // Invalidar todas as páginas de cache do usuário (até 10 páginas)
        for ($page = 1; $page <= 10; $page++) {
            for ($perPage = 10; $perPage <= 200; $perPage += 10) {
                $cacheKey = "tasks:user:{$userId}:page:{$page}:per_page:{$perPage}";
                Cache::forget($cacheKey);
            }
        }

        // Invalidar cache geral (primeiras 5 páginas)
        for ($page = 1; $page <= 5; $page++) {
            for ($perPage = 10; $perPage <= 200; $perPage += 10) {
                $cacheKey = "tasks:all:page:{$page}:per_page:{$perPage}";
                Cache::forget($cacheKey);
            }
        }

        Log::info("Cache invalidado para usuário {$userId}", [
            'user_id' => $userId,
            'action' => 'cache_invalidation'
        ]);
    }

    /**
     * Invalida cache de uma tarefa específica
     */
    public function invalidateTaskCache(int $taskId): void
    {
        $cacheKey = "task:{$taskId}";
        Cache::forget($cacheKey);

        Log::info("Cache invalidado para task {$taskId}", [
            'task_id' => $taskId,
            'action' => 'task_cache_invalidation'
        ]);
    }

    /**
     * Limpa todo o cache de tarefas
     * Usar com cuidado - apenas para manutenção
     */
    public function clearAllTasksCache(): void
    {
        // Esta é uma operação custosa - use apenas quando necessário
        $patterns = [
            'tasks:user:*',
            'tasks:all:*',
            'task:*'
        ];

        foreach ($patterns as $pattern) {
            // No Redis, podemos usar KEYS, mas cuidado em produção
            try {
                $keys = Cache::getStore()->connection()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getStore()->connection()->del($keys);
                }
            } catch (\Exception $e) {
                Log::warning("Erro ao limpar cache com pattern {$pattern}: " . $e->getMessage());
            }
        }

        Log::warning("Cache completo de tasks foi limpo", [
            'action' => 'full_cache_clear'
        ]);
    }

    /**
     * Obtém estatísticas do cache
     */
    public function getCacheStats(): array
    {
        try {
            $redis = Cache::getStore()->connection();
            $info = $redis->info();
            
            return [
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'used_memory_human' => $info['used_memory_human'] ?? 'unknown',
                'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                'hit_ratio' => $this->calculateHitRatio($info),
                'uptime_in_seconds' => $info['uptime_in_seconds'] ?? 0
            ];
        } catch (\Exception $e) {
            return [
                'error' => 'Não foi possível obter estatísticas do Redis',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Calcula o hit ratio do cache
     */
    private function calculateHitRatio(array $info): string
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;
        
        if ($total === 0) {
            return '0%';
        }
        
        $ratio = ($hits / $total) * 100;
        return number_format($ratio, 2) . '%';
    }

    /**
     * Aquece o cache com dados mais acessados
     * Útil após deploy ou limpeza de cache
     */
    public function warmCache(): array
    {
        $warmedKeys = 0;
        $errors = [];

        try {
            // Aquecer primeiras páginas de todas as tasks
            for ($page = 1; $page <= 3; $page++) {
                $this->getAllTasks($page, 50);
                $warmedKeys++;
            }

            // Aquecer tasks dos primeiros usuários (IDs 1-20)
            for ($userId = 1; $userId <= 20; $userId++) {
                try {
                    $this->getTasksByUserId($userId, 1, 50);
                    $warmedKeys++;
                } catch (\Exception $e) {
                    $errors[] = "User {$userId}: " . $e->getMessage();
                }
            }

            Log::info("Cache aquecido com sucesso", [
                'warmed_keys' => $warmedKeys,
                'errors_count' => count($errors)
            ]);

            return [
                'success' => true,
                'warmed_keys' => $warmedKeys,
                'errors' => $errors
            ];

        } catch (\Exception $e) {
            Log::error("Erro ao aquecer cache: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}