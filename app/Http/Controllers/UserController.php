<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
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
            
            // Verificar se o usuário possui tarefas associadas
            $tasksCount = Task::where('user_id', $userId)->count();
            
            if ($tasksCount > 0) {
                return response()->json([
                    'error' => 'Não é possível excluir o usuário.',
                    'message' => "O usuário possui {$tasksCount} tarefas associadas. Exclua ou reatribua as tarefas primeiro.",
                    'tasks_count' => $tasksCount
                ], 409); // Conflict - violação de integridade referencial
            }

            $user->delete();

            return response()->json([
                'message' => 'Usuário excluído com sucesso.',
                'user_id' => $userId
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
}