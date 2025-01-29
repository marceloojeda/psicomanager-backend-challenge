<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Interfaces\UserRepositoryInterface;
use App\Domain\Entities\UserEntity;
use App\Infrastructure\Persistence\Models\User;

class UserRepository implements UserRepositoryInterface {

    public function getUserRepository(int $userId): ?UserEntity {
        $user = User::findOrFail($userId);

        return new UserEntity(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            createdAt: $user->createdAt
        );
    }

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

    public function createUserRepository(array $data): UserEntity {
        $user = User::create($data);

        return new UserEntity(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            createdAt: $user->createdAt
        );
    }
}

