<?php

namespace App\Observers;

use App\Services\ActionLogService;


trait UserObserver {

    protected static function boot()
    {
        parent::boot();

        static::created(function ($user) {
            ActionLogService::log('create', 'users', $user);
        });

        static::deleted(function ($user) {
            ActionLogService::log('delete', 'users', $user);
        });

    }
}