<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\JWTService;
use App\Traits\LogsOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use LogsOperations;

    protected JWTService $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentication"},
     *     summary="Login do usuário",
     *     description="Autentica um usuário e retorna token JWT",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="joao@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Dados de entrada inválidos",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function login(Request $request)
    {
        // Validação dos dados de entrada
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'validation_errors' => $validator->errors()
            ], 422);
        }

        try {
            // Buscar usuário pelo email (incluindo soft deleted)
            $user = User::withTrashed()->where('email', $request->get('email'))->first();

            // Verificar se usuário existe e senha está correta
            if (!$user || !Hash::check($request->get('password'), $user->password)) {
                
                // Log de tentativa de login falhada
                $this->logOperation('login_failed', [
                    'email' => $request->get('email'),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'reason' => !$user ? 'user_not_found' : 'invalid_password'
                ], 'warning');

                return response()->json([
                    'error' => 'Credenciais inválidas',
                    'message' => 'Email ou senha incorretos'
                ], 401);
            }

            // Permitir login de usuários soft deleted para que possam recuperar a conta
            // mas incluir informação sobre o status no response

            // Gerar tokens JWT
            $accessToken = $this->jwtService->generateToken($user);
            $refreshToken = $this->jwtService->generateRefreshToken($user);

            // Preparar dados do usuário (sem campos sensíveis)
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'is_deleted' => $user->deleted_at !== null // Informar se o usuário está soft deleted
            ];

            // Log de login bem-sucedido
            $this->logOperation('login_success', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'expires_in' => $this->jwtService->getExpirationTime(),
                'user' => $userData
            ], 200);

        } catch (\Exception $e) {
            // Log de erro interno
            $this->logError('login_error', $e, [
                'email' => $request->get('email')
            ]);

            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => 'Falha durante autenticação'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh",
     *     tags={"Authentication"},
     *     summary="Renovar token JWT",
     *     description="Renova o token de acesso usando refresh token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"refresh_token"},
     *             @OA\Property(property="refresh_token", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token renovado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="refresh_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="bearer"),
     *             @OA\Property(property="expires_in", type="integer", example=3600)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Refresh token inválido",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function refresh(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Refresh token obrigatório'
            ], 422);
        }

        try {
            $refreshToken = $request->get('refresh_token');

            // Validar refresh token
            if (!$this->jwtService->validateRefreshToken($refreshToken)) {
                return response()->json([
                    'error' => 'Refresh token inválido ou expirado'
                ], 401);
            }

            // Extrair usuário do refresh token
            $user = $this->jwtService->getUserFromToken($refreshToken);
            if (!$user) {
                return response()->json([
                    'error' => 'Usuário não encontrado'
                ], 401);
            }

            // Gerar novos tokens
            $newAccessToken = $this->jwtService->generateToken($user);
            $newRefreshToken = $this->jwtService->generateRefreshToken($user);

            $this->logOperation('token_refresh', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'access_token' => $newAccessToken,
                'refresh_token' => $newRefreshToken,
                'token_type' => 'bearer',
                'expires_in' => $this->jwtService->getExpirationTime()
            ], 200);

        } catch (\Exception $e) {
            $this->logError('token_refresh_error', $e);
            
            return response()->json([
                'error' => 'Erro ao renovar token'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout do usuário",
     *     description="Invalida o token atual (logout)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logout realizado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function logout(Request $request)
    {
        try {
            // Pegar usuário do request (injetado pelo middleware)
            $user = $request->attributes->get('auth_user');
            
            if ($user) {
                $this->logOperation('logout_success', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $request->ip()
                ]);
            }

            // Nota: Em uma implementação completa, aqui você adicionaria o token
            // a uma blacklist para impedir seu uso futuro
            
            return response()->json([
                'message' => 'Logout realizado com sucesso'
            ], 200);

        } catch (\Exception $e) {
            $this->logError('logout_error', $e);
            
            return response()->json([
                'error' => 'Erro durante logout'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/auth/me",
     *     tags={"Authentication"},
     *     summary="Dados do usuário autenticado",
     *     description="Retorna informações do usuário logado",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dados do usuário retornados com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="created_at", type="string"),
     *                 @OA\Property(property="updated_at", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function me(Request $request)
    {
        try {
            $user = $request->attributes->get('auth_user');
            
            if (!$user) {
                return response()->json([
                    'error' => 'Usuário não autenticado'
                ], 401);
            }

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at
                ]
            ], 200);

        } catch (\Exception $e) {
            $this->logError('me_endpoint_error', $e);
            
            return response()->json([
                'error' => 'Erro ao obter dados do usuário'
            ], 500);
        }
    }
}