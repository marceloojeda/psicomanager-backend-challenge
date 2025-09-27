<?php

namespace App\Providers;

use App\Services\TaskCacheService;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(TaskCacheService::class, function ($app) {
            return new TaskCacheService();
        });
    }
}