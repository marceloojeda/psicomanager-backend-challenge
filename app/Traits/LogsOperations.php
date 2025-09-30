<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Carbon\Carbon;

trait LogsOperations
{
    /**
     * Log a business operation
     */
    protected function logOperation(string $operation, array $data = [], string $level = 'info'): void
    {
        $operationLogger = app('operation.logger');
        
        $logData = [
            'timestamp' => Carbon::now()->toISOString(),
            'operation' => $operation,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data
        ];
        
        $operationLogger->log($level, $operation, $logData);
    }
    
    /**
     * Log an audit event (for sensitive operations)
     */
    protected function logAudit(string $action, array $data = []): void
    {
        $auditLogger = app('audit.logger');
        
        $auditData = [
            'timestamp' => Carbon::now()->toISOString(),
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data
        ];
        
        $auditLogger->warning($action, $auditData);
    }
    
    /**
     * Log a failed operation
     */
    protected function logError(string $operation, \Exception $exception, array $context = []): void
    {
        $operationLogger = app('operation.logger');
        
        $errorData = [
            'timestamp' => Carbon::now()->toISOString(),
            'operation' => $operation,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context
        ];
        
        $operationLogger->error($operation . ' failed', $errorData);
    }
}