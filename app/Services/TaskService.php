<?php

namespace App\Services;

use App\Models\Task;
use Exception;

use Illuminate\Http\Response;
use App\Services\ServiceResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\TaskResource;

class TaskService extends ServiceResponse
{
    /**
     * Método para filtrar tarefas com base nos filtros fornecidos.
     *
     * @param array $filters Um array contendo os filtros (id e nome).
     * @return bool
     */
    public function getFilteredTasks(array $filters): bool
    {
        try {
            $object = Task::when(!empty($filters['user_id']), function ($query) use ($filters) {
                return $query->where('user_id', $filters['user_id']);
            })
            ->get();

            $data = $object->all();

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Tarefas listadas com sucesso!');
            $this->setData($data);
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
            $task = Task::where('id', $id)->firstOrFail();

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Tarefa encontrada com sucesso!');
            $this->setData($task->toArray());
            $this->setResource(TaskResource::class);

            return true;

        } catch (ModelNotFoundException $e) {
            $this->setStatus(Response::HTTP_NOT_FOUND);
            $this->setMessage('Tarefa não encontrada.');
            $this->setError($e->getMessage());
        } catch (Exception $e) {
            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao buscar tarefa. Tente novamente mais tarde.');
            $this->setError($e->getMessage());
        }

        return false;
    }
}
