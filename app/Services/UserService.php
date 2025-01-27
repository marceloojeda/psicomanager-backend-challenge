<?php

namespace App\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Método para filtrar usuários com base nos filtros fornecidos.
     *
     * @param array $filters Um array contendo os filtros (id e nome).
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws Exception Se ocorrer um erro durante o processamento.
     */
    public function getFilteredUsers(array $filters)
    {
        try {
            $query = User::query();

            if (!empty($filters['id'])) {
                $query->where('id', $filters['id']);
            }

            if (!empty($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }

            return $query->get();
        } catch (Exception $e) {
            Log::error('Erro ao filtrar usuários: ' . $e->getMessage());

            throw new Exception('Erro ao processar a requisição. Tente novamente mais tarde.');
        }
    }
}
