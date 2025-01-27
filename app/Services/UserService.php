<?php

namespace App\Services;

use App\Models\User;
use Exception;

use Illuminate\Http\Response;
use App\Services\ServiceResponse;
use App\Http\Resources\UserResource;

class UserService extends ServiceResponse
{
    /**
     * Método para filtrar usuários com base nos filtros fornecidos.
     *
     * @param array $filters Um array contendo os filtros (id e nome).
     * @return array
     */
    public function getFilteredUsers(array $filters): array
    {
        try {
            $object = User::when(!empty($filters['id']), function ($query) use ($filters) {
                return $query->where('id', $filters['id']);
            })
            ->when(!empty($filters['name']), function ($query) use ($filters) {
                return $query->where('name', 'like', '%' . $filters['name'] . '%');
            })
            ->get();

            $data = $object->all();

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Usuários listados com sucesso!');
            $this->setData($data);
            $this->setCollection(UserResource::class);
        } catch (Exception $e) {
            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao listar usuários.Tente novamente mais tarde.');
            $this->setError($e->getMessage());
        }

        return $this->getResponse();
    }

    /**
     * Método para pegar unico usuário pelo id.
     *
     * @param User $user Model do usuario passado.
     * @return array
     */
    public function getUser(User $user): array
    {
        try {
            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Usuários encontrado com sucesso!');
            $this->setData($user->toArray());
            $this->setCollection(UserResource::class);
        } catch (Exception $e) {
            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao buscar o usuário. Tente novamente mais tarde.');
            $this->setError($e->getMessage());
        }

        return $this->getResponse();
    }
}
