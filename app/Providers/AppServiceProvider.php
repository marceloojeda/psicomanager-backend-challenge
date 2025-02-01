<?php

namespace App\Providers;

use App\Http\Services\LogService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(LogService::class, function ($app) {
            return new LogService();
        });
    }
}
