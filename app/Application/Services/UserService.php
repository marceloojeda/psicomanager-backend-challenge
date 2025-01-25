<?php

namespace App\Application\Services;

use App\Domain\Models\User;
use App\Domain\Repositories\UserRepositoryInterface;

class UserService
{
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function createUser($name, $email)
    {
        $user = new User(null, $name, $email);
        $this->userRepository->save($user);
    }

    public function getUsers()
    {
        return $this->userRepository->findAll();
    }

    public function deleteUser($userId)
    {
        $user = $this->userRepository->findById($userId);
        $this->userRepository->delete($user);
    }
}
