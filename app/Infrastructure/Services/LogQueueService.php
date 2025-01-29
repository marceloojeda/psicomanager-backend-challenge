<?php

namespace App\Infrastructure\Services;

use App\Jobs\ProcessLogJob;
use Illuminate\Support\Facades\Queue;

class LogQueueService
{
    public const INFO = 'info';
    public const DEBUG = 'debug';
    public const ERROR = 'error';
    public const WARNING = 'warning';
    public const CRITICAL = 'critical';

    /**
     * Salva um log no sistema.
     *
     * @param string $level Nível do log (use as constantes da classe)
     * @param string $message Mensagem do log
     * @param array $context Contexto adicional (opcional)
     */
    public static function save(string $level, string $message, array $context = [])
    {
        $logData = [
            'level_name' => $level,
            'message' => $message,
            'context' => $context
        ];

        Queue::push(new ProcessLogJob($logData));
    }
}
