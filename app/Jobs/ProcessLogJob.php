<?php

namespace App\Jobs;

use App\Infrastructure\Persistence\Models\Log;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessLogJob extends Job {

    protected $record;

    /**
     * Construtor do Job
     *
     * @param array $record
     */
    public function __construct(array $record)
    {
        $this->record = $record;
    }

    /**
     * Executa o trabalho de gravação do log no banco de dados.
     *
     * @return void
     */
    public function handle()
    {
        $log = new Log();
        $log->level = $this->record['level_name'];
        $log->message = $this->record['message'];
        $log->context = json_encode($this->record['context']);
        $log->save();
    }
}
