<?php

namespace Infrastructure\Services;

use Illuminate\Support\Facades\Log;

class LoggingService {
    public function log($message) {
        Log::info($message);
    }
}
