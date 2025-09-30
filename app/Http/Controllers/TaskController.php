<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\TaskCacheService;
use App\Traits\LogsOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

        // Implementar autorização baseada em roles
        $requestedUserId = $request->input('user_id');
        
        if ($requestedUserId) {
            // Validação do user_id
            if (!is_numeric($requestedUserId) || $requestedUserId <= 0) {
                return response()->json([
                    'error' => 'user_id inválido. Deve ser um número positivo.'
                ], 400);
            }
            
            // Verificar autorização: usuário comum só pode ver suas próprias tarefas
            if (!$authUser->isAdmin() && $authUser->id != $requestedUserId) {
                return response()->json([
                    'error' => 'Acesso negado',
                    'message' => 'Você só pode visualizar suas próprias tarefas',
                    'user_role' => $authUser->role
                ], 403);
            }
        } else {
            // Se não foi especificado user_id, definir comportamento baseado na role
            if ($authUser->isUser()) {
                // Usuários comuns só veem suas próprias tarefas
                $requestedUserId = $authUser->id;
            }
            // Admins podem ver todas as tarefas (não definir requestedUserId)
        }

        // Filtro por user_id com cache otimizado
        if ($requestedUserId) {
            // Log task query by user
            $this->logOperation('tasks_queried_by_user', [
                'requested_user_id' => $requestedUserId,
                'authenticated_user_id' => $authUser?->id,
                'authenticated_user_email' => $authUser?->email,
                'page' => $page,
                'per_page' => $perPage,
                'cache_enabled' => true
            ]);

            // Usar cache para consultas por usuário (mais comuns)
            try {
                $cachedResult = $this->cacheService->getTasksByUserId((int) $requestedUserId, $page, $perPage);
                
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
                    'user_id' => $requestedUserId
                ]);
                return $this->getTasksWithoutCache($requestedUserId, $page, $perPage);
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
     * @OA\Post(
     *     path="/tasks",
     *     tags={"Tasks"},
     *     summary="Criar nova tarefa",
     *     description="Cria uma nova tarefa para o usuário autenticado",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"description"},
     *             @OA\Property(property="description", type="string", example="Concluir relatório mensal"),
     *             @OA\Property(property="status", type="string", enum={"pendente", "em progresso", "concluida"}, example="pendente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Tarefa criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tarefa criada com sucesso"),
     *             @OA\Property(property="data", ref="#/components/schemas/Task")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Dados de entrada inválidos")
     * )
     */
    public function store(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        
        // Validação dos dados
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|min:3|max:500',
            'status' => 'sometimes|string|in:pendente,em progresso,concluida,cancelada'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 400);
        }
        
        $validatedData = $request->only(['description', 'status']);
        
        // Definir status padrão se não fornecido
        if (!isset($validatedData['status'])) {
            $validatedData['status'] = 'pendente';
        }
        
        // Associar tarefa ao usuário autenticado
        $validatedData['user_id'] = $user->id;
        
        try {
            $task = Task::create($validatedData);
            
            // Log da criação
            $this->logOperation('task_created', [
                'task_id' => $task->id,
                'user_id' => $user->id,
                'description' => $task->description,
                'status' => $task->status,
                'created_at' => $task->created_at->toISOString()
            ]);
            
            // Limpar cache relacionado
            $this->cacheService->invalidateUserCache($user->id);
            
            return response()->json([
                'message' => 'Tarefa criada com sucesso',
                'data' => $task
            ], 201);
            
        } catch (\Exception $e) {
            $this->logError('task_creation_error', $e, [
                'user_id' => $user->id,
                'request_data' => $validatedData
            ]);
            
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Não foi possível criar a tarefa'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/tasks/{taskId}",
     *     tags={"Tasks"},
     *     summary="Atualizar tarefa",
     *     description="Atualiza uma tarefa existente. Usuários comuns só podem atualizar suas próprias tarefas",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="description", type="string", example="Relatório mensal atualizado"),
     *             @OA\Property(property="status", type="string", enum={"pendente", "em progresso", "concluida"}, example="em progresso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarefa atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tarefa atualizada com sucesso"),
     *             @OA\Property(property="data", ref="#/components/schemas/Task")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Tarefa não encontrada"),
     *     @OA\Response(response=403, description="Acesso negado - não é proprietário da tarefa")
     * )
     */
    public function update(Request $request, $taskId)
    {
        $user = $request->attributes->get('auth_user');
        
        // Validação do ID
        if (!is_numeric($taskId) || $taskId <= 0) {
            return response()->json([
                'error' => 'ID inválido',
                'message' => 'O ID da tarefa deve ser um número positivo.'
            ], 400);
        }
        
        // Buscar tarefa
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json([
                'error' => 'Tarefa não encontrada',
                'message' => "Tarefa com ID {$taskId} não existe."
            ], 404);
        }
        
        // Verificar se usuário pode atualizar esta tarefa
        if ($user->isUser() && $task->user_id !== $user->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você só pode atualizar suas próprias tarefas'
            ], 403);
        }
        
        // Validação dos dados
        $validator = Validator::make($request->all(), [
            'description' => 'sometimes|required|string|min:3|max:500',
            'status' => 'sometimes|required|string|in:pendente,em progresso,concluida,cancelada'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'messages' => $validator->errors()
            ], 400);
        }

        $validated = $validator->validated();

        try {
            // Guardar dados para log antes da atualização
            $taskData = [
                'task_id' => $task->id,
                'user_id' => $task->user_id,
                'description' => $task->description,
                'status' => $task->status,
                'created_at' => $task->created_at->toISOString()
            ];
            
            // Atualizar apenas campos fornecidos
            $task->fill($validated);
            $task->save();
            
            // Log da atualização
            $this->logOperation('task_updated', [
                'task_id' => $task->id,
                'updated_by' => $user->id,
                'old_data' => $taskData,
                'new_data' => $task->toArray(),
                'updated_at' => $task->updated_at->toISOString()
            ]);
            
            // Limpar cache relacionado
            $this->cacheService->invalidateUserCache($task->user_id);
            if ($user->isAdmin()) {
                $this->cacheService->clearAllTasksCache();
            }
            
            return response()->json([
                'message' => 'Tarefa atualizada com sucesso',
                'data' => $task->fresh()
            ]);
            
        } catch (\Exception $e) {
            $this->logError('task_update_error', $e, [
                'task_id' => $taskId,
                'user_id' => $user->id,
                'request_data' => $validated
            ]);
            
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Não foi possível atualizar a tarefa'
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/tasks/{taskId}",
     *     tags={"Tasks"},
     *     summary="Excluir tarefa",
     *     description="Exclui uma tarefa existente. Usuários comuns só podem excluir suas próprias tarefas",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tarefa excluída com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Tarefa excluída com sucesso"),
     *             @OA\Property(property="task_id", type="string", example="1")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Tarefa não encontrada"),
     *     @OA\Response(response=403, description="Acesso negado - não é proprietário da tarefa")
     * )
     */
    public function destroy(Request $request, $taskId)
    {
        $user = $request->attributes->get('auth_user');
        
        // Validação do ID
        if (!is_numeric($taskId) || $taskId <= 0) {
            return response()->json([
                'error' => 'ID inválido',
                'message' => 'O ID da tarefa deve ser um número positivo.'
            ], 400);
        }
        
        // Buscar tarefa
        $task = Task::find($taskId);
        if (!$task) {
            return response()->json([
                'error' => 'Tarefa não encontrada',
                'message' => "Tarefa com ID {$taskId} não existe."
            ], 404);
        }
        
        // Verificar se usuário pode excluir esta tarefa
        if ($user->isUser() && $task->user_id !== $user->id) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você só pode excluir suas próprias tarefas'
            ], 403);
        }
        
        try {
            // Guardar dados para log antes da exclusão
            $taskData = [
                'task_id' => $task->id,
                'user_id' => $task->user_id,
                'description' => $task->description,
                'status' => $task->status,
                'created_at' => $task->created_at->toISOString()
            ];
            
            // Excluir tarefa
            $task->delete();
            
            // Log da exclusão
            $this->logOperation('task_deleted', [
                'deleted_by' => $user->id,
                'deleted_at' => now()->toISOString(),
                'task_data' => $taskData
            ]);
            
            // Limpar cache relacionado
            $this->cacheService->invalidateUserCache($taskData['user_id']);
            if ($user->isAdmin()) {
                $this->cacheService->clearAllTasksCache();
            }
            
            return response()->json([
                'message' => 'Tarefa excluída com sucesso',
                'task_id' => (string)$taskId
            ]);
            
        } catch (\Exception $e) {
            $this->logError('task_deletion_error', $e, [
                'task_id' => $taskId,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Não foi possível excluir a tarefa'
            ], 500);
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
