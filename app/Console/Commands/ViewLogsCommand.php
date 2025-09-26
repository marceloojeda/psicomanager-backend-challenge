<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class ViewLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'logs:view 
                           {type=operations : Type of logs to view (operations, audit, all)}
                           {--lines=20 : Number of lines to show}
                           {--date= : Date to view logs for (Y-m-d format)}';

    /**
     * The console command description.
     */
    protected $description = 'View application logs with filtering options';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $lines = $this->option('lines');
        $date = $this->option('date') ?: Carbon::now()->format('Y-m-d');

        switch ($type) {
            case 'operations':
                $this->showOperationLogs($date, $lines);
                break;
            case 'audit':
                $this->showAuditLogs($date, $lines);
                break;
            case 'all':
                $this->showOperationLogs($date, $lines);
                $this->line('');
                $this->showAuditLogs($date, $lines);
                break;
            default:
                $this->error('Invalid log type. Use: operations, audit, or all');
                return;
        }
    }

    private function showOperationLogs($date, $lines)
    {
        $logFile = storage_path("logs/operations-{$date}.log");
        
        if (!file_exists($logFile)) {
            $this->warn("No operation logs found for {$date}");
            return;
        }

        $this->info("=== OPERATION LOGS ({$date}) ===");
        $this->displayLogFile($logFile, $lines);
    }

    private function showAuditLogs($date, $lines)
    {
        $logFile = storage_path("logs/audit-{$date}.log");
        
        if (!file_exists($logFile)) {
            $this->warn("No audit logs found for {$date}");
            return;
        }

        $this->info("=== AUDIT LOGS ({$date}) ===");
        $this->displayLogFile($logFile, $lines);
    }

    private function displayLogFile($logFile, $lines)
    {
        $content = file_get_contents($logFile);
        $logEntries = array_filter(explode("\n", $content));
        
        // Get the last N lines
        $recentEntries = array_slice($logEntries, -$lines);
        
        foreach ($recentEntries as $entry) {
            $logData = json_decode($entry, true);
            
            if (!$logData) {
                continue;
            }

            $timestamp = $logData['context']['timestamp'] ?? $logData['datetime'];
            $operation = $logData['message'] ?? 'Unknown';
            $level = $logData['level_name'] ?? 'INFO';
            
            // Color coding based on level
            $color = $this->getColorForLevel($level);
            
            $this->line("<{$color}>[{$timestamp}] {$level}: {$operation}</{$color}>");
            
            // Show additional context if available
            if (isset($logData['context']['data'])) {
                $data = $logData['context']['data'];
                foreach ($data as $key => $value) {
                    if (is_scalar($value)) {
                        $this->line("  └─ {$key}: {$value}");
                    }
                }
            }
            
            $this->line('');
        }
        
        $this->info("Showing last {$lines} entries from " . basename($logFile));
    }

    private function getColorForLevel($level)
    {
        return match($level) {
            'ERROR' => 'error',
            'WARNING' => 'comment',
            'INFO' => 'info',
            default => 'line'
        };
    }
}