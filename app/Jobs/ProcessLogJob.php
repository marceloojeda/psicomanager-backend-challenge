<?php

namespace App\Jobs;

use App\Infrastructure\Persistence\Models\Log as LogEloquent;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
use App\Infrastructure\Services\ElasticsearchLogService;

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
        $log = new LogEloquent();
        $log->level = $this->record['level_name'];
        $log->message = $this->record['message'];
        $log->context = json_encode($this->record['context']);

        $saveElasticSearch = empty(env('ELASTICSEARCH_HOST')) === false;

        if ($saveElasticSearch === true) {
            $elas = new ElasticsearchLogService();
            $elas->saveToLogsIndex($this->record);
        } else {
            $log->save();
        }
    }
}
