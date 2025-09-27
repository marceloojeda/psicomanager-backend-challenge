<?php

namespace App\Http\Middleware;

use App\Services\JWTService;
use App\Traits\LogsOperations;
use Closure;
use Illuminate\Http\Request;

class JWTAuthMiddleware
{
    use LogsOperations;

    protected JWTService $jwtService;

    public function __construct(JWTService $jwtService)
    {
        $this->jwtService = $jwtService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Extrair token do header Authorization
            $token = $this->extractToken($request);
            
            if (!$token) {
                $this->logOperation('auth_failed', [
                    'reason' => 'missing_token',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'endpoint' => $request->path()
                ], 'warning');

                return response()->json([
                    'error' => 'Token de acesso obrigatório',
                    'message' => 'Forneça um token válido no header Authorization'
                ], 401);
            }

            // Validar token
            try {
                $isValidToken = $this->jwtService->validateToken($token);
            } catch (\Exception $e) {
                $this->logOperation('auth_failed', [
                    'reason' => 'invalid_token',
                    'error_message' => $e->getMessage(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'endpoint' => $request->path()
                ], 'warning');

                return response()->json([
                    'error' => 'Token inválido ou expirado',
                    'message' => 'O token fornecido é inválido ou expirou'
                ], 401);
            }

            if (!$isValidToken) {
                $this->logOperation('auth_failed', [
                    'reason' => 'invalid_token_result',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'endpoint' => $request->path()
                ], 'warning');

                return response()->json([
                    'error' => 'Token inválido ou expirado',
                    'message' => 'O token fornecido é inválido ou expirou'
                ], 401);
            }

            // Verificar se token está próximo do vencimento (aviso)
            if ($this->jwtService->isTokenExpiring($token)) {
                // Adicionar header indicando que o token está próximo do vencimento
                $response = $next($request);
                $response->headers->set('X-Token-Expiring', 'true');
                $response->headers->set('X-Token-Expires-In', $this->jwtService->getTokenTimeToExpire($token));
                return $response;
            }

            // Extrair usuário do token
            $user = $this->jwtService->getUserFromToken($token);
            
            if (!$user) {
                $this->logOperation('auth_failed', [
                    'reason' => 'user_not_found_from_token',
                    'ip_address' => $request->ip(),
                    'endpoint' => $request->path()
                ], 'warning');

                return response()->json([
                    'error' => 'Usuário não encontrado',
                    'message' => 'O usuário associado ao token não existe'
                ], 401);
            }

            // Verificar se usuário não foi soft deleted
            if ($user->deleted_at !== null) {
                $this->logOperation('auth_failed', [
                    'reason' => 'user_deleted',
                    'user_id' => $user->id,
                    'ip_address' => $request->ip(),
                    'endpoint' => $request->path()
                ], 'warning');

                return response()->json([
                    'error' => 'Usuário inativo',
                    'message' => 'Este usuário foi desativado'
                ], 401);
            }

            // Adicionar usuário ao request para uso nos controllers
            $request->attributes->add(['auth_user' => $user]);
            
            // Log de acesso bem-sucedido (apenas em debug mode para evitar spam)
            if (env('APP_DEBUG', false)) {
                $this->logOperation('auth_success', [
                    'user_id' => $user->id,
                    'endpoint' => $request->path(),
                    'method' => $request->method()
                ], 'debug');
            }

            return $next($request);

        } catch (\Exception $e) {
            $this->logError('jwt_middleware_error', $e, [
                'ip_address' => $request->ip(),
                'endpoint' => $request->path(),
                'method' => $request->method()
            ]);

            return response()->json([
                'error' => 'Erro interno de autenticação',
                'message' => 'Falha durante verificação do token'
            ], 500);
        }
    }

    /**
     * Extract JWT token from request headers
     *
     * @param Request $request
     * @return string|null
     */
    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return null;
        }

        // Verificar formato: "Bearer {token}"
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        // Extrair token removendo "Bearer "
        $token = substr($authHeader, 7);
        
        // Validar se token não está vazio
        return !empty(trim($token)) ? trim($token) : null;
    }
}