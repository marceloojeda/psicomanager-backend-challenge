<?php

namespace App\Services;

use App\Models\ActionLog;
use Illuminate\Support\Facades\Log;

class ActionLogService
{
    public static function log($action, $table, $data)
    {
        ActionLog::create([
            'action' => $action,
            'table' => $table,
            'data' => $data,
            'user_id' => 0, // TODO: pegar user id do Auth facade após implementação de login
        ]);
    }
}