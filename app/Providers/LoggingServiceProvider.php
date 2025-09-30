<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

class LoggingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureOperationLogging();
    }

    /**
     * Configure structured logging for business operations
     */
    private function configureOperationLogging(): void
    {
        // Create a custom logger for operations
        $operationLogger = new Logger('operations');
        
        // Add rotating file handler for operations
        $operationHandler = new RotatingFileHandler(
            storage_path('logs/operations.log'),
            7, // Keep 7 days of logs
            Logger::INFO
        );
        
        // Set JSON formatter for structured logging
        $operationHandler->setFormatter(new JsonFormatter());
        $operationLogger->pushHandler($operationHandler);
        
        // Register the logger in the container
        $this->app->instance('operation.logger', $operationLogger);
        
        // Create audit logger for sensitive operations
        $auditLogger = new Logger('audit');
        $auditHandler = new RotatingFileHandler(
            storage_path('logs/audit.log'),
            30, // Keep 30 days of audit logs
            Logger::WARNING
        );
        $auditHandler->setFormatter(new JsonFormatter());
        $auditLogger->pushHandler($auditHandler);
        
        $this->app->instance('audit.logger', $auditLogger);
    }
}