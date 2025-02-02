<?php

namespace App\Http\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

interface IUserRepository
{
    public function findAll(): Collection;
    public function findById(int $userId): User|null;
    public function findByFilter(Request $request): Collection;
    public function findUserIsAdmin(): User;
    public function persist(Request $request): User;
    public function delete(int $userId): void;
}