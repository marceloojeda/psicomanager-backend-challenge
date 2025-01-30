<?php

namespace App\Domain\Interfaces;

/**
 * Interface que define os métodos necessários para a manipulação das tarefas.
 *
 * Esta interface serve como contrato para a implementação de repositórios que lidam com a recuperação de tarefas.
 * Ela define os métodos que devem ser implementados para obter tarefas específicas e filtradas.
 */
interface LogRepositoryInterface {

    /**
     * Obtém uma lista de logs filtradas de acordo com os critérios fornecidos.
     *
     * @param array $params Array associativo com os parametros para busca e definição de pagina e quantidade.
     * @return array Lista de logs que atendem aos filtros fornecidos.
     */
    public function getFilteredLogs(array $params): array;
}
