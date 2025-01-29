<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Interfaces\UserRepositoryInterface;
use App\Domain\Entities\UserEntity;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Repositório para manipulação dos usuários.
 *
 * Esta classe implementa os métodos definidos na interface UserRepositoryInterface.
 * Ela é responsável pelas operações de acesso e manipulação dos dados de usuários no banco de dados.
 */
class UserRepository implements UserRepositoryInterface {

    /**
     * Obtém um usuário pelo seu ID.
     *
     * @param int $userId O ID do usuário a ser recuperado.
     * @return UserEntity O usuário correspondente ao ID fornecido.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Caso o usuário não seja encontrado.
     */
    public function getUserRepository(int $userId): ?UserEntity {
        $user = User::findOrFail($userId);

        return new UserEntity(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            createdAt: $user->createdAt
        );
    }

    /**
     * Obtém uma lista de usuários filtrados conforme os critérios fornecidos.
     *
     * @param array $filters Filtros para buscar os usuários, como 'id' e 'name'.
     * @return UserEntity[] Lista de usuários filtrados, cada um representado como uma instância de UserEntity.
     */
    public function getFilteredUsersRepository(array $filters): array {
        $object = User::when(!empty($filters['id']), function ($query) use ($filters) {
            return $query->where('id', $filters['id']);
        })
        ->when(!empty($filters['name']), function ($query) use ($filters) {
            return $query->where('name', 'like', '%' . $filters['name'] . '%');
        })
        ->get();

        return $object->map(fn($user) => new UserEntity(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            createdAt: $user->createdAt
        ))->toArray();
    }

    /**
     * Cria um novo usuário no banco de dados.
     *
     * @param array $data Dados para criar o novo usuário (nome, email e senha).
     * @return UserEntity O usuário recém-criado, representado como uma instância de UserEntity.
     */
    public function createUserRepository(array $data): UserEntity {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return new UserEntity(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            createdAt: $user->createdAt
        );
    }

    /**
     * Exclui um usuário do banco de dados.
     *
     * @param int $id O ID do usuário a ser excluído.
     * @return bool Retorna true se o usuário foi excluído com sucesso.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Caso o usuário não seja encontrado.
     */
    public function deleteUserRepository(int $id): bool {
        $user = User::where('id', $id)->firstOrFail();
        $user->delete();

        return true;
    }
}
