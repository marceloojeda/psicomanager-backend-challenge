<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    function index(Request $request)
    {
        $query = User::query();

        // Filtro por ID
        if ($request->has('id')) {
            $query->where('id', $request->get('id'));
        }

        // Filtro por nome (busca parcial, case-insensitive)
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->get('name') . '%');
        }

        // Paginação
        $perPage = $request->get('per_page', 15); // Default: 15 itens por página
        $perPage = min($perPage, 100); // Máximo: 100 itens por página

        if ($request->has('page') || $request->has('per_page')) {
            $users = $query->paginate($perPage);
            return UserResource::collection($users);
        } else {
            // Mantém compatibilidade: sem parâmetros de paginação, retorna tudo
            $users = $query->get();
            return UserResource::collection($users);
        }
    }
    
    function get($userId)
    {
        // Validação do ID - deve ser numérico e positivo
        if (!is_numeric($userId) || $userId <= 0) {
            return response()->json([
                'error' => 'ID inválido. O ID deve ser um número positivo.'
            ], 400);
        }

        try {
            $user = User::where('id', $userId)->firstOrFail();
            return new UserResource($user);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Usuário não encontrado.',
                'message' => "Usuário com ID {$userId} não existe."
            ], 404);
        }
    }
    
    function store(Request $request)
    {
        // Validação dos dados de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6|max:255',
        ]);

        // Retorna erro de validação se houver
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'validation_errors' => $validator->errors()
            ], 422);
        }

        // Cria o usuário com senha criptografada
        $userData = $request->only(['name', 'email']);
        $userData['password'] = Hash::make($request->get('password'));

        $user = User::create($userData);

        // Retorna usando UserResource para não expor campos sensíveis
        return response()->json(new UserResource($user), 201);
    }

    function delete($userId)
    {
        // Validação do ID - deve ser numérico e positivo
        if (!is_numeric($userId) || $userId <= 0) {
            return response()->json([
                'error' => 'ID inválido. O ID deve ser um número positivo.'
            ], 400);
        }

        try {
            $user = User::where('id', $userId)->firstOrFail();
            
            // Com soft delete, não precisamos verificar tarefas associadas
            // O usuário será marcado como excluído, mantendo a integridade referencial
            $tasksCount = Task::where('user_id', $userId)->count();
            
            $user->delete(); // Soft delete - apenas marca deleted_at

            return response()->json([
                'message' => 'Usuário excluído com sucesso (soft delete).',
                'user_id' => $userId,
                'deleted_at' => $user->deleted_at,
                'tasks_preserved' => $tasksCount > 0 ? "Foram preservadas {$tasksCount} tarefas associadas." : 'Nenhuma tarefa associada.'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Usuário não encontrado.',
                'message' => "Usuário com ID {$userId} não existe."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor.',
                'message' => 'Falha ao excluir o usuário.'
            ], 500);
        }
    }

    /**
     * Restore a soft deleted user
     */
    public function restore($userId)
    {
        try {
            // Validar ID do usuário
            if (!is_numeric($userId) || $userId <= 0) {
                return response()->json([
                    'error' => 'ID do usuário inválido.',
                    'message' => 'O ID deve ser um número inteiro positivo.'
                ], 400);
            }

            // Buscar usuário apenas entre os soft deleted
            $user = User::onlyTrashed()->where('id', $userId)->first();
            
            if (!$user) {
                // Verificar se o usuário existe e não foi soft deleted
                $activeUser = User::find($userId);
                if ($activeUser) {
                    return response()->json([
                        'error' => 'Usuário não foi excluído.',
                        'message' => "Usuário com ID {$userId} está ativo e não precisa ser restaurado."
                    ], 409); // Conflict
                }
                
                return response()->json([
                    'error' => 'Usuário não encontrado.',
                    'message' => "Usuário com ID {$userId} não existe ou não foi soft deleted."
                ], 404);
            }

            // Contar tarefas associadas para informar o usuário
            $tasksCount = Task::where('user_id', $userId)->count();
            
            // Restaurar o usuário (remove o deleted_at)
            $user->restore();

            return response()->json([
                'message' => 'Usuário restaurado com sucesso.',
                'user_id' => $userId,
                'restored_at' => Carbon::now()->toISOString(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'tasks_associated' => $tasksCount > 0 ? "Usuário possui {$tasksCount} tarefas associadas." : 'Nenhuma tarefa associada.'
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor.',
                'message' => 'Falha ao restaurar o usuário.'
            ], 500);
        }
    }
}