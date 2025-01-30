<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\StatusCodeEnum;

/**
 * Middleware JwtMiddleware
 *
 * Verifica se o usuário está autenticado utilizando JWT.
 */
class JwtMiddleware
{
    /**
     * Manipula a solicitação HTTP.
     *
     * Este middleware verifica se o usuário está autenticado com JWT.
     * Caso contrário, retorna uma resposta de erro com código de status HTTP 401 (não autorizado).
     *
     * @param Request $request A solicitação HTTP recebida.
     * @param Closure $next Função que chama o próximo middleware ou controlador.
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response Resposta HTTP.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!auth('api')->check()) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 401);
        }

        return $next($request);
    }
}
