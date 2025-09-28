<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Task;
use App\Traits\LogsOperations;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use LogsOperations;
    
    /**
     * @OA\Get(
     *     path="/users",
     *     tags={"Users"},
     *     summary="Lista todos os usuários",
     *     description="Retorna uma lista paginada de usuários com opções de filtros",
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Filtrar por ID específico do usuário",
     *         required=false,
     *         @OA\Schema(type="integer", example=77)
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filtrar por nome do usuário (busca parcial)",
     *         required=false,
     *         @OA\Schema(type="string", example="João")
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
     *         description="Quantidade de itens por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de usuários retornada com sucesso",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/User")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
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
            // Convertendo manualmente para evitar problemas com ResponseFactory no Lumen
            $usersArray = [];
            foreach ($users->items() as $user) {
                $usersArray[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            }
            
            return [
                'data' => $usersArray,
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage()
            ];
        } else {
            // Mantém compatibilidade: sem parâmetros de paginação, retorna tudo
            $users = $query->get();
            $usersArray = [];
            foreach ($users as $user) {
                $usersArray[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            }
            return $usersArray;
        }
    }
    
    /**
     * @OA\Get(
     *     path="/users/{userId}",
     *     tags={"Users"},
     *     summary="Buscar usuário por ID",
     *     description="Retorna os dados de um usuário específico",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID do usuário",
     *         required=true,
     *         @OA\Schema(type="integer", example=77)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Dados do usuário retornados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/User"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="ID inválido",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
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
            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Usuário não encontrado.',
                'message' => "Usuário com ID {$userId} não existe."
            ], 404);
        }
    }
    
    /**
     * @OA\Post(
     *     path="/users",
     *     tags={"Users"},
     *     summary="Criar novo usuário",
     *     description="Cria um novo usuário no sistema",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="João Silva", description="Nome completo do usuário"),
     *             @OA\Property(property="email", type="string", format="email", example="joao@example.com", description="E-mail único do usuário"),
     *             @OA\Property(property="password", type="string", example="123456", description="Senha (mínimo 6 caracteres)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/User"
     *             ),
     *             @OA\Property(property="message", type="string", example="Usuário criado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Email já está em uso",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados de entrada inválidos",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    function store(Request $request)
    {
        // Validação dos dados de entrada
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6|max:255',
            'role' => 'sometimes|in:user,admin', // Role opcional, padrão é 'user'
        ]);

        // Retorna erro de validação se houver
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'validation_errors' => $validator->errors()
            ], 422);
        }

        // Cria o usuário com senha criptografada
        $userData = $request->only(['name', 'email', 'role']);
        $userData['password'] = Hash::make($request->get('password'));
        
        // Se role não foi fornecido, usar 'user' como padrão
        if (!isset($userData['role'])) {
            $userData['role'] = 'user';
        }

        try {
            $user = User::create($userData);

            // Log successful user creation
            $this->logOperation('user_created', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'created_at' => $user->created_at->toISOString()
            ]);

            // Retorna usando dados estruturados para não expor campos sensíveis
            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'message' => 'Usuário criado com sucesso'
            ], 201);
            
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

    /**
     * @OA\Delete(
     *     path="/users/{userId}",
     *     tags={"Users"},
     *     summary="Excluir usuário (soft delete)",
     *     description="Exclui um usuário do sistema usando soft delete. O usuário e suas tarefas associadas são marcados como excluídos",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID do usuário a ser excluído",
     *         required=true,
     *         @OA\Schema(type="integer", example=77)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário excluído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Usuário excluído com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="ID inválido ou usuário já foi excluído",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
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
     * @OA\Patch(
     *     path="/users/{userId}/restore",
     *     tags={"Users"},
     *     summary="Restaurar usuário excluído",
     *     description="Restaura um usuário que foi excluído com soft delete, juntamente com suas tarefas associadas",
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="ID do usuário a ser restaurado",
     *         required=true,
     *         @OA\Schema(type="integer", example=77)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário restaurado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/User"
     *             ),
     *             @OA\Property(property="message", type="string", example="Usuário restaurado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="ID inválido ou usuário não está excluído",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflito - já existe usuário ativo com este email",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
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