<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\UserRepositoryInterface;
use App\Models\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function getAll()
    {
        return User::all();
    }

    public function create($data)
    {
        return User::create($data);
    }

    public function delete($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();
    }
}
