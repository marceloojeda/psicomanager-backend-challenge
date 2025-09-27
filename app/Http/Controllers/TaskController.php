<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\TaskCacheService;
use App\Traits\LogsOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    use LogsOperations;
    
    protected TaskCacheService $cacheService;
    
    /**
     * Create a new controller instance.
     */
    public function __construct(TaskCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * @OA\Get(
     *     path="/tasks",
     *     tags={"Tasks"},
     *     summary="Lista todas as tarefas",
     *     description="Retorna uma lista paginada de tarefas com opções de filtros",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filtrar tarefas por ID do usuário responsável",
     *         required=false,
     *         @OA\Schema(type="integer", example=77)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número da página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Quantidade de itens por página (máximo 50)",
     *         required=false,
     *         @OA\Schema(type="integer", example=50, maximum=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tarefas retornada com sucesso",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Task")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Parâmetros inválidos",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    function index(Request $request)
    {
        // Validação dos parâmetros
        $page = max(1, (int) $request->get('page', 1));
        $perPage = min(max(1, (int) $request->get('per_page', 50)), 200);

        // Obter usuário autenticado
        $authUser = $request->attributes->get('auth_user');

        // Filtro por user_id com cache otimizado
        if ($request->has('user_id')) {
            $userId = $request->input('user_id');
            
            // Validação do user_id
            if (!is_numeric($userId) || $userId <= 0) {
                return response()->json([
                    'error' => 'user_id inválido. Deve ser um número positivo.'
                ], 400);
            }

            // Log task query by user
            $this->logOperation('tasks_queried_by_user', [
                'requested_user_id' => $userId,
                'authenticated_user_id' => $authUser?->id,
                'authenticated_user_email' => $authUser?->email,
                'page' => $page,
                'per_page' => $perPage,
                'cache_enabled' => true
            ]);

            // Usar cache para consultas por usuário (mais comuns)
            try {
                $cachedResult = $this->cacheService->getTasksByUserId((int) $userId, $page, $perPage);
                
                // Transformar dados para manter compatibilidade com formato anterior
                return response()->json([
                    'data' => $this->transformTasksData($cachedResult['data']),
                    'current_page' => $cachedResult['pagination']['current_page'],
                    'per_page' => $cachedResult['pagination']['per_page'],
                    'total' => $cachedResult['pagination']['total'],
                    'last_page' => $cachedResult['pagination']['last_page'],
                    'cached_at' => $cachedResult['cached_at']
                ], 200);
            } catch (\Exception $e) {
                // Fallback sem cache em caso de erro
                Log::warning('Erro no cache, usando consulta direta', [
                    'error' => $e->getMessage(),
                    'user_id' => $userId
                ]);
                return $this->getTasksWithoutCache($userId, $page, $perPage);
            }
        }

        // Para consultas gerais, usar cache também
        try {
            $cachedResult = $this->cacheService->getAllTasks($page, $perPage);
            
            return response()->json([
                'data' => $this->transformTasksData($cachedResult['data']),
                'current_page' => $cachedResult['pagination']['current_page'],
                'per_page' => $cachedResult['pagination']['per_page'],
                'total' => $cachedResult['pagination']['total'],
                'last_page' => $cachedResult['pagination']['last_page'],
                'cached_at' => $cachedResult['cached_at']
            ], 200);
        } catch (\Exception $e) {
            // Fallback sem cache
            Log::warning('Erro no cache geral, usando consulta direta', [
                'error' => $e->getMessage()
            ]);
            return $this->getAllTasksWithoutCache($page, $perPage);
        }
    }

    /**
     * @OA\Get(
     *     path="/tasks/{taskId}",
     *     tags={"Tasks"},
     *     summary="Buscar tarefa por ID",
     *     description="Retorna os dados de uma tarefa específica",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         description="ID da tarefa",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados da tarefa retornados com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Task")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="ID inválido",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tarefa não encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    function get(Request $request, $taskId)
    {
        // Validação do taskId
        if (!is_numeric($taskId) || $taskId <= 0) {
            return response()->json([
                'error' => 'ID inválido. O ID deve ser um número positivo.'
            ], 400);
        }

        // Obter usuário autenticado
        $authUser = $request->attributes->get('auth_user');

        try {
            // Usar cache para busca individual
            $cachedResult = $this->cacheService->getTaskById((int) $taskId);
            
            if (!$cachedResult) {
                return response()->json([
                    'error' => 'Tarefa não encontrada.',
                    'message' => "Tarefa com ID {$taskId} não existe."
                ], 404);
            }

            return response()->json([
                'data' => $this->transformTaskData($cachedResult['data']),
                'cached_at' => $cachedResult['cached_at']
            ], 200);

        } catch (\Exception $e) {
            // Fallback sem cache
            Log::warning('Erro no cache individual, usando consulta direta', [
                'error' => $e->getMessage(),
                'task_id' => $taskId
            ]);

            try {
                $task = Task::with('user')->where('id', $taskId)->firstOrFail();
                return response()->json(['data' => $this->transformTaskData($task)], 200);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                return response()->json([
                    'error' => 'Tarefa não encontrada.',
                    'message' => "Tarefa com ID {$taskId} não existe."
                ], 404);
            }
        }
    }

    /**
     * Transforma array de tasks para formato consistente
     */
    private function transformTasksData($tasks): array
    {
        if (empty($tasks)) {
            return [];
        }

        return array_map([$this, 'transformTaskData'], $tasks);
    }

    /**
     * Transforma uma task individual para formato consistente
     */
    private function transformTaskData($task): array
    {
        // Se for um modelo Eloquent, converter para array
        if (is_object($task) && method_exists($task, 'toArray')) {
            $taskArray = $task->toArray();
        } else {
            $taskArray = (array) $task;
        }

        return [
            'id' => $taskArray['id'],
            'description' => $taskArray['description'],
            'status' => $taskArray['status'],
            'user_id' => $taskArray['user_id'],
            'created_at' => $taskArray['created_at'],
            'updated_at' => $taskArray['updated_at'],
            'user' => isset($taskArray['user']) ? [
                'id' => $taskArray['user']['id'],
                'name' => $taskArray['user']['name'],
                'email' => $taskArray['user']['email'],
                'created_at' => $taskArray['user']['created_at'],
                'updated_at' => $taskArray['user']['updated_at']
            ] : null
        ];
    }

    /**
     * Fallback para consultas por usuário sem cache
     */
    private function getTasksWithoutCache(int $userId, int $page, int $perPage): array
    {
        $query = Task::with('user')->where('user_id', $userId);
        $tasks = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $this->transformTasksData($tasks->items()),
            'current_page' => $tasks->currentPage(),
            'per_page' => $tasks->perPage(),
            'total' => $tasks->total(),
            'last_page' => $tasks->lastPage(),
            'cache_status' => 'disabled'
        ];
    }

    /**
     * Fallback para consultas gerais sem cache
     */
    private function getAllTasksWithoutCache(int $page, int $perPage): array
    {
        $query = Task::with('user');
        $tasks = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $this->transformTasksData($tasks->items()),
            'current_page' => $tasks->currentPage(),
            'per_page' => $tasks->perPage(),
            'total' => $tasks->total(),
            'last_page' => $tasks->lastPage(),
            'cache_status' => 'disabled'
        ];
    }

    /**
     * Endpoint para limpar cache (para admins)
     * GET /tasks/cache/clear
     */
    public function clearCache(): array
    {
        try {
            $this->cacheService->clearAllTasksCache();
            
            return [
                'message' => 'Cache de tarefas limpo com sucesso',
                'cleared_at' => now()->toISOString()
            ];
        } catch (\Exception $e) {
            Log::error('Erro ao limpar cache: ' . $e->getMessage());
            
            return [
                'error' => 'Erro ao limpar cache',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Endpoint para estatísticas do cache
     * GET /tasks/cache/stats
     */
    public function cacheStats(): array
    {
        return $this->cacheService->getCacheStats();
    }

    /**
     * Endpoint para aquecer o cache
     * POST /tasks/cache/warm
     */
    public function warmCache(): array
    {
        return $this->cacheService->warmCache();
    }
}
