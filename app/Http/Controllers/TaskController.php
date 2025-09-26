<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Traits\LogsOperations;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use LogsOperations;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * @OA\Get(
     *     path="/tasks",
     *     tags={"Tasks"},
     *     summary="Lista todas as tarefas",
     *     description="Retorna uma lista paginada de tarefas com opções de filtros",
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
        $query = Task::query();

        // Filtro por user_id com otimização
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
                'user_id' => $userId,
                'per_page' => min($request->get('per_page', 50), 200)
            ]);

            $query->where('user_id', $userId);
        }

        // Paginação para performance
        $perPage = min($request->get('per_page', 50), 200); // Máximo 200 por página
        
        // Ordenação padrão por ID para consistência na paginação
        $query->orderBy('id', 'desc');

        // Usar paginação sempre para grandes volumes
        if ($request->has('user_id') || $request->has('page') || $request->has('per_page')) {
            $results = $query->paginate($perPage);
            return response()->json($results);
        } else {
            // Para consultas gerais sem filtro, limitar a 100 registros
            $results = $query->limit(100)->get();
            return response()->json($results);
        }
    }

    /**
     * @OA\Get(
     *     path="/tasks/{taskId}",
     *     tags={"Tasks"},
     *     summary="Buscar tarefa por ID",
     *     description="Retorna os dados de uma tarefa específica",
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
    function get($taskId)
    {
        // Validação do taskId
        if (!is_numeric($taskId) || $taskId <= 0) {
            return response()->json([
                'error' => 'ID inválido. O ID deve ser um número positivo.'
            ], 400);
        }

        try {
            $task = Task::where('id', $taskId)->firstOrFail();
            return response()->json($task);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Tarefa não encontrada.',
                'message' => "Tarefa com ID {$taskId} não existe."
            ], 404);
        }
    }
}
