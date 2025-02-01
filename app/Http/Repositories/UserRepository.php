<?php

namespace App\Http\Repositories;

use App\Http\Repositories\Interfaces\IUserRepository;
use App\Models\User;

class UserRepository implements IUserRepository
{
    public function findAll()
    {
        return User::all();
    }

    public function persist($request)
    {
        return User::create($request->all());
    }

    public function delete($userId)
    {
        $user = User::where('id', $userId)->firstOrFail();
        $user->delete();
    }
}