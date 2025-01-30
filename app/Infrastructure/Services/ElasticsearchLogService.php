<?php

namespace App\Infrastructure\Services;

use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Transport\Exception\TransportException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Exception;
use Illuminate\Support\Facades\Log;

class ElasticsearchLogService
{
    protected $client;
    private $index = 'logs3';

    public function __construct()
    {
        try {
            $hosts = [
                env('ELASTICSEARCH_HOST', 'http://elastic:elastic@localhost:9200')
            ];

            if (empty($hosts)) {
                throw new Exception('servidor do Elasticsearch inválido.');
            }

            $this->client = ClientBuilder::create()->setHosts($hosts)->build();

            if (!$this->client->ping()) {
                throw new Exception('Falha na conexão com o Elasticsearch.');
            }

        } catch (NoNodeAvailableException $e) {
            throw new Exception('Elasticsearch não está acessível: ' . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception('Erro ao tentar conectar ao Elasticsearch: ' . $e->getMessage());
        }
    }

    /**
     * Cria o índice 'logs' no Elasticsearch
     */
    public function createLogsIndex(): void
    {
        try {
            $params = [
                'index' => $this->index ,
                'body'  => [
                    'settings' => [
                        'number_of_shards' => 1,
                    ],
                    'mappings' => [
                        'properties' => [
                            'level' => ['type' => 'text'],
                            'message' => ['type' => 'text'],
                            'context' => ['type' => 'text'],
                            'user_id' => ['type' => 'text'],
                            'user_name' => ['type' => 'text'],
                            'ip_address' => ['type' => 'text'],
                            'request' => ['type' => 'text'],
                            'date' => ['type' => 'text'],
                        ]
                    ]
                ]
            ];

            $this->client->indices()->create($params);

            Log::info('index de logs criado com sucesso no elasticsearc!');
        } catch (TransportException $e) {
            Log::error('Erro de transporte ao salvar indice de logs no Elasticsearch: ' . $e->getMessage());
        } catch (NoNodeAvailableException $e) {
            Log::error('Não foi possível conectar ao Elasticsearch para criar indice de logs: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('Erro desconhecido ao criar o índice de logs no Elasticsearch: ' . $e->getMessage());
        }
    }

    /**
     * Salva um documento no índice 'logs'
     *
     * @param array $data
     * @return mixed
     */
    public function saveToLogsIndex(array $data): bool
    {
        $id = random_int(1000000000, 9999999999);

        if (empty($data['context']['operation']['id']) == false) {
            $id = $data['context']['operation']['id'];
        }
        try {
            $params = [
                'index' =>  $this->index,
                'id'    => $id,
                'body'  => [
                    'level' => $data['level_name'],
                    'message' => $data['message'],
                    'context' => $data['context']['operation'],
                    'user_id' => '',//auth()->id,
                    'user_name' => '',
                    'ip_address' => '',
                    'request' => $data['context']['request'],
                    'date' => date('dd/mm/YYYY')
                ]
            ];


            $this->client->index($params);

            Log::info('logs no elasticsearch salvo com sucesso');

            return true;
        } catch (TransportException $e) {
            Log::error("Erro de transporte ao salvar log no Elasticsearch: " . $e->getMessage());
            return false;
        } catch (NoNodeAvailableException $e) {
            Log::error("Não foi possível conectar ao Elasticsearch para salvar a log: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            Log::error("Erro desconhecido ao salvar log no Elasticsearch: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica a conexão com o Elasticsearch
     */
    public function ping(): mixed
    {
        try {
            return $this->client->ping();
        } catch (NoNodeAvailableException $e) {
            return 'Não foi possível conectar ao Elasticsearch: ' . $e->getMessage();
        }
    }

    public function fetchAllLogs(): array
    {
        try {

            $params = [
                'scroll' => '30s',
                'size'   => 50,
                'index' =>  $this->index,
                'body'   => [
                    'query' => [
                        'match_all' => new \stdClass()
                    ]
                ]
            ];

            $response = $this->client->search($params);

            return $response['hits']['hits'];
        } catch (TransportException $e) {
            Log::error("Erro de transporte para pegar logs: " . $e->getMessage());
            return [];
        } catch (NoNodeAvailableException $e) {
            Log::error("Não foi possível conectar ao Elasticsearch para buscar as logs: " . $e->getMessage());
            return [];
        } catch (Exception $e) {
            Log::error("Erro desconhecido ao pegar logs no elasticsearch: " . $e->getMessage());
            return [];
        }
    }
}
