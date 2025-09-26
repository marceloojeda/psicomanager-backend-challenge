<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Http\Resources\UserResource;
use App\Traits\LogsOperations;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use LogsOperations;
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

        try {
            $user = User::create($userData);

            // Log successful user creation
            $this->logOperation('user_created', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'created_at' => $user->created_at->toISOString()
            ]);

            // Retorna usando UserResource para não expor campos sensíveis
            return response()->json(new UserResource($user), 201);
            
        } catch (\Exception $e) {
            // Log error
            $this->logError('user_creation_failed', $e, [
                'attempted_data' => $request->only(['name', 'email'])
            ]);
            
            return response()->json([
                'error' => 'Erro interno do servidor.',
                'message' => 'Falha ao criar usuário.'
            ], 500);
        }
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

            // Log successful user deletion (audit event)
            $this->logAudit('user_soft_deleted', [
                'user_id' => $userId,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'tasks_preserved' => $tasksCount,
                'deleted_at' => $user->deleted_at->toISOString()
            ]);

            return response()->json([
                'message' => 'Usuário excluído com sucesso (soft delete).',
                'user_id' => $userId,
                'deleted_at' => $user->deleted_at,
                'tasks_preserved' => $tasksCount > 0 ? "Foram preservadas {$tasksCount} tarefas associadas." : 'Nenhuma tarefa associada.'
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Log failed deletion attempt
            $this->logOperation('user_deletion_failed', [
                'user_id' => $userId,
                'reason' => 'user_not_found'
            ], 'warning');
            
            return response()->json([
                'error' => 'Usuário não encontrado.',
                'message' => "Usuário com ID {$userId} não existe."
            ], 404);
        } catch (\Exception $e) {
            // Log error
            $this->logError('user_deletion_error', $e, [
                'user_id' => $userId
            ]);
            
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

            // Log successful user restoration (audit event)
            $this->logAudit('user_restored', [
                'user_id' => $userId,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'tasks_associated' => $tasksCount,
                'restored_at' => Carbon::now()->toISOString()
            ]);

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
            // Log error
            $this->logError('user_restoration_error', $e, [
                'user_id' => $userId
            ]);
            
            return response()->json([
                'error' => 'Erro interno do servidor.',
                'message' => 'Falha ao restaurar o usuário.'
            ], 500);
        }
    }
}