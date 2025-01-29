<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\TaskEntity;

/**
 * Interface que define os métodos necessários para a manipulação das tarefas.
 *
 * Esta interface serve como contrato para a implementação de repositórios que lidam com a recuperação de tarefas.
 * Ela define os métodos que devem ser implementados para obter tarefas específicas e filtradas.
 */
interface TaskRepositoryInterface {

    /**
     * Obtém uma tarefa pelo seu ID.
     *
     * @param int $taskId O ID da tarefa a ser recuperada.
     * @return TaskEntity|null Retorna a tarefa correspondente ou null se não for encontrada.
     */
    public function getTask(int $taskId): ?TaskEntity;

    /**
     * Obtém uma lista de tarefas filtradas de acordo com os critérios fornecidos.
     *
     * @param array $params Array associativo com os parametros para busca e definição de pagina e quantidade.
     * @return TaskEntity[] Lista de tarefas que atendem aos filtros fornecidos.
     */
    public function getFilteredTasks(array $params): array;
}
