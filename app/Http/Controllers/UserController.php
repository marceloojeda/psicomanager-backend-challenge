<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    function index()
    {
        $id     = request()->query('id');
        $name   = request()->query('name');

        $usersQuery = User::query()->select('id', 'name', 'email');

        if ($id) {
            $usersQuery = User::where('id', $id);
        }

        if ($name) {
            $usersQuery = User::where('name', 'LIKE', "%$name%");
        }

        $users = $usersQuery->get();

        return response()->json($users);
    }
    
    function store(Request $request)
    {
        try {

            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8',
            ];
    
            $validatedUser = $this->validate($request, $rules);
    
            $validatedUser['password'] = app('hash')->make($validatedUser['password']);
    
            $user = User::create($validatedUser);

            return response()->json(['id' => $user->id, 'name' => $user->name, 'email' => $user->email], Response::HTTP_CREATED);

        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação','errors' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $th) {
            return response()->json([ 'message' => 'Erro ao criar usuário','error' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function delete($userId)
    {
        try {
            $user = User::where('id', $userId)->firstOrFail();

            $user->delete();

            return response(['message' => 'Usuário removido com sucesso'], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Usuário não encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Erro ao excluir usuário', 'error' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    function get(int $userId)
    {
        try {
            $user = User::select('id', 'name', 'email')->findOrFail($userId);
            return response()->json(['id' => $user->id, 'name' => $user->name, 'email' => $user->email], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Usuário não encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Erro ao buscar usuário', 'error' => $th->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}