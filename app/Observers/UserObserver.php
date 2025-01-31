<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function created(User $user)
    {
        Log::info('User created: ', ['id' => $user->id, 'name' => $user->name]);
    }

    public function updated(User $user)
    {
        Log::info('User updated: ', ['id' => $user->id, 'name' => $user->name]);
    }

    public function deleted(User $user)
    {
        Log::info('User deleted: ', ['id' => $user->id, 'name' => $user->name]);
    }
}