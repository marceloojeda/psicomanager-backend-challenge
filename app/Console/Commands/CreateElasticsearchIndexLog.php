<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elastic\Elasticsearch\ClientBuilder;

/**
 * Classe para criar um índice no Elasticsearch.
 *
 * Esta classe define um comando Artisan para criar o índice de vendas no Elasticsearch,
 * configurando as definições e mapeamentos necessários para o armazenamento dos dados.
 */
class CreateElasticsearchIndexLog extends Command
{
    /**
     * O nome e a assinatura do comando Artisan.
     *
     * @var string
     */
    protected $signature = 'elasticsearch:create-index-log';

    /**
     * A descrição do comando Artisan.
     *
     * @var string
     */
    protected $description = 'Criar index para o Elasticsearch de vendas';

    /**
     * Lógica principal do comando.
     *
     * Este método cria um índice no Elasticsearch chamado "sales" com as configurações
     * e mapeamentos especificados. Se houver um erro durante a criação, ele será capturado
     * e exibido no console.
     *
     * @return void
     */
    public function handle()
    {
        $client = ClientBuilder::create()->setHosts([env('ELASTICSEARCH_HOST')])->build();

        $params = [
            'index' => 'ddddddss',
            'body'  => [
                'settings' => [
                    'number_of_shards' => 1,
                ],
                'mappings' => [
                    'properties' => [
                        'name' => ['type' => 'text'],
                        'email' => ['type' => 'text'],
                        'value' => ['type' => 'text'],
                        'comission' => ['type' => 'text'],
                        'date' => ['type' => 'text'],
                    ]
                ]
            ]
        ];

        try {
            $client->indices()->create($params);
            $this->info('Index de vendas criado');
        } catch (\Exception $e) {
            $this->info('Erro: ' . $e->getMessage());
        }
    }
}
