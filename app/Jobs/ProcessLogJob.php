<?php

namespace App\Jobs;

use App\Infrastructure\Persistence\Models\Log as LogEloquent;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;

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
            //$id = $this->record['context']['id'] ?? null;
            $this->indexToElasticsearch($this->record['context']['operation']);
        } else {
            $log->save();
        }
    }

    /**
     * Indexar dados de logs no Elasticsearch.
     */
    private function indexToElasticsearch(array $log): void
    {
        try {
            $client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();

            $params = [
                'index' => 'logs3',
                'id'    => $log['id'],
                'body'  => $log
            ];

            $client->index($params);

            Log::info('Log salvo no elasticSearch !!!');

        } catch (\Exception $e) {
            Log::error('Erro Log no elasticSearch = ' . $e->getMessage());
        }
    }
}
