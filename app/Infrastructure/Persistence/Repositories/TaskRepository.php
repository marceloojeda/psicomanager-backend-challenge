<?php

namespace App\Infrastructure\Persistence\Repositories;

use App\Domain\Interfaces\TaskRepositoryInterface;
use App\Domain\Entities\TaskEntity;
use App\Infrastructure\Persistence\Models\Task;

/**
 * Repositório para manipulação das tarefas.
 *
 * Esta classe é responsável por implementar os métodos definidos na interface TaskRepositoryInterface.
 * Ela realiza operações de acesso e manipulação de dados de tarefas no banco de dados, utilizando o modelo Task.
 */
class TaskRepository implements TaskRepositoryInterface {

    /**
     * Obtém uma tarefa pelo seu ID.
     *
     * @param int $taskId O ID da tarefa a ser recuperada.
     * @return TaskEntity A tarefa correspondente ao ID fornecido.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Caso a tarefa não seja encontrada.
     */
    public function getTask(int $taskId): ?TaskEntity {
        $task = Task::findOrFail($taskId);

        return new TaskEntity(
            id: $task->id,
            userId: $task->user_id,
            description: $task->description,
            status: $task->status,
            createdAt: $task->created_at
        );
    }

    /**
     * Obtém uma lista de tarefas filtradas conforme os critérios fornecidos.
     *
     * @param array $filters Filtros para buscar as tarefas, como 'user_id'.
     * @return TaskEntity[] Lista de tarefas filtradas, cada uma representada como uma instância de TaskEntity.
     */
    public function getFilteredTasks(array $filters): array {
        $object = Task::when(!empty($filters['user_id']), function ($query) use ($filters) {
            return $query->where('user_id', $filters['user_id']);
        })
        ->get();

        return $object->map(fn($task) => new TaskEntity(
            id: $task->id,
            userId: $task->user_id,
            description: $task->description,
            status: $task->status,
            createdAt: $task->created_at
        ))->toArray();
    }
}
