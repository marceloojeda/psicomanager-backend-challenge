<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\UserEntity;

interface UserRepositoryInterface {
    public function getUserRepository(int $userId): ?UserEntity;
    public function getFilteredUsersRepository(array $filters): array;
    public function createUserRepository(array $data): UserEntity;
    public function deleteUserRepository(int $id): bool;
}
