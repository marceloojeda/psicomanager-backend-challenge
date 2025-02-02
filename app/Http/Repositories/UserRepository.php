<?php

namespace App\Http\Repositories;

use App\Http\Repositories\Interfaces\IUserRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserRepository implements IUserRepository
{
    public function findAll(): Collection
    {
        return User::all();
    }

    public function findById($userId): User
    {
        return User::where('id', $userId)->firstOrFail();
    }

    public function findByFilter($request): Collection
    {
        $users = User::query();

        if ($request->has('name')) {
            $users->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('id')) {
            $users->where('id', $request->id);
        }

        return $users->get();
    }

    public function findUserIsAdmin(): User
    {
        return User::where('is_admin', true)->first();
    }

    public function persist($request): User
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return $user;
    }

    public function delete($userId): void
    {
        $user = User::where('id', $userId)->firstOrFail();
        $user->delete();
    }
}