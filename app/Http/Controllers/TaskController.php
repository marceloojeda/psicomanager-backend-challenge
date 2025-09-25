<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

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
