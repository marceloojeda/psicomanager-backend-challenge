<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
            return response()->json($users);
        } else {
            // Mantém compatibilidade: sem parâmetros de paginação, retorna tudo
            $users = $query->get();
            return response()->json($users);
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
            return response()->json($user);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Usuário não encontrado.',
                'message' => "Usuário com ID {$userId} não existe."
            ], 404);
        }
    }
    
    function store(Request $request)
    {
        $user = User::create($request->all());

        return response()->json($user);
    }

    function delete($userId)
    {
        $user = User::where('id', $userId)->firstOrFail();

        $user->delete();

        return response("Usuario excluido com sucesso", Response::HTTP_ACCEPTED);
    }
}