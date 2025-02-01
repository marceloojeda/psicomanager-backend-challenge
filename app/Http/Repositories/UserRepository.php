<?php

namespace App\Http\Repositories;

use App\Http\Repositories\Interfaces\IUserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository implements IUserRepository
{
    public function findAll()
    {
        return User::all();
    }

    public function findById($userId)
    {
        return User::where('id', $userId)->get();
    }

    public function findByFilter($request)
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

    public function findUserIsAdmin()
    {
        return User::where('is_admin', true)->first();
    }

    public function persist($request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        return $user;
    }

    public function delete($userId)
    {
        $user = User::where('id', $userId)->firstOrFail();
        $user->delete();
    }
}