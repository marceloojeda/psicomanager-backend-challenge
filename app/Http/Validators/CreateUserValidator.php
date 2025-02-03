<?php

namespace App\Http\Validators;

use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CreateUserValidator
{
    public static function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ], [
            'name.required' => 'O nome é obrigatório.',
            'name.string' => 'O nome deve ter caracteres válidos.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ser um endereço válido.',
            'email.unique' => 'Este email já está em uso. Por favor, escolha outro.',
            'password.required' => 'A senha é obrigatória.',
            'password.string' => 'A senha deve ser uma string válida.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
        ]);

        if ($validator->fails()) {
            throw new ApiException('Erro de validação', 422, $validator->errors());
        }
    }
}