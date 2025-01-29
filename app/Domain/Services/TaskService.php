<?php

namespace App\Domain\Services;

use App\Domain\Interfaces\TaskRepositoryInterface;
use App\Infrastructure\Services\ServiceResponse;
use Illuminate\Http\Response;
use App\Application\Resources\TaskResource;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskService extends ServiceResponse {
    public function __construct(private TaskRepositoryInterface $taskRepository) {}

    /**
     * Método para filtrar tarefas com base nos filtros fornecidos.
     *
     * @param array $filters Um array contendo os filtros (id e nome).
     * @return bool
     */
    public function getFilteredTasks(array $filters): bool
    {
        try {
            $data = $this->taskRepository->getFilteredTasks($filters);

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Tarefas listadas com sucesso!');
            $this->setCollection($data);
            $this->setResource(TaskResource::class);

            return true;
        } catch (Exception $e) {
            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao listar tarefas.Tente novamente mais tarde.');
            $this->setError($e->getMessage());

            return false;
        }
    }

    /**
     * Método para pegar unica tarefa pelo id.
     *
     * @param int $id ID da tarefa.
     * @return bool
     */
    public function getTask(int $id): bool
    {
        try {
            $data = $this->taskRepository->getTask($id);

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Tarefa encontrada com sucesso!');
            $this->setCollectionItem($data);
            $this->setResource(TaskResource::class);

            return true;

        } catch (ModelNotFoundException $e) {
            $this->setStatus(Response::HTTP_NOT_FOUND);
            $this->setMessage('Tarefa não encontrada.');
            $this->setError($e->getMessage());
        } catch (Exception $e) {
            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao encontrar tarefa. Tente novamente mais tarde.');
            $this->setError($e->getMessage());
        }

        return false;
    }
}
