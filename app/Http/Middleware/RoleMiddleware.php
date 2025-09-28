<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role)
    {
        // Get the authenticated user from the request (set by JWT middleware)
        $user = $request->attributes->get('auth_user');
        
        if (!$user) {
            return response()->json([
                'error' => 'Usuário não autenticado',
                'message' => 'Token JWT é obrigatório para acessar este endpoint'
            ], 401);
        }

        // Check if user has the required role
        if (!$user->hasRole($role)) {
            return response()->json([
                'error' => 'Acesso negado',
                'message' => 'Você não possui permissão para acessar este recurso',
                'required_role' => $role,
                'user_role' => $user->role
            ], 403);
        }

        return $next($request);
    }
}