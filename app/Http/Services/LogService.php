<?php

namespace App\Http\Services;

use App\Models\Log;
use Illuminate\Support\Facades\Log as FileLog;

class LogService
{
    private function saveLog(string $level, string $message, array $context = []): void
    {
        FileLog::log($level, $message, $context);

        Log::create([
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ]);
    }

    public function logInfo(string $message, array $context = []): void
    {
        $this->saveLog('info', $message, $context);
    }

    public function logWarning(string $message, array $context = []): void
    {
        $this->saveLog('warning', $message, $context);
    }

    public function logError(string $message, array $context = []): void
    {
        $this->saveLog('error', $message, $context);
    }

    public function logCritical(string $message, array $context = []): void
    {
        $this->saveLog('critical', $message, $context);
    }
}