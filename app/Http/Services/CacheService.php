<?php

namespace App\Http\Services;

use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\Cache;

class CacheService
{
    public function remember(string $tag, string $key, int $minutes, Closure $callback): mixed
    {
        return Cache::tags($tag)->remember($key, Carbon::now()->addMinutes($minutes), $callback);
    }

    public function forget(string $tag, string $key): bool
    {
        return Cache::tags($tag)->forget($key);
    }
    
    public function forgetByTag(string $tag): void
    {
        Cache::tags($tag)->flush();
    }
}