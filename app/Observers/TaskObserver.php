<?php

namespace App\Observers;

use App\Services\ActionLogService;


trait TaskObserver {

    protected static function boot()
    {
        parent::boot();

        static::created(function ($task) {
            ActionLogService::log('create', 'tasks', $task);
        });

        static::deleted(function ($task) {
            ActionLogService::log('delete', 'tasks', $task);
        });

        static::updated(function ($task) {
            ActionLogService::log('update', 'tasks', $task);
        });

    }
}