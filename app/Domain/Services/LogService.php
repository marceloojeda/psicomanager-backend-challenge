<?php

namespace App\Domain\Services;

use App\Domain\Interfaces\LogRepositoryInterface;
use App\Infrastructure\Services\ServiceResponse;
use Illuminate\Http\Response;
use App\Application\Resources\TaskResource;
use Exception;

class LogService extends ServiceResponse {
    public function __construct(private LogRepositoryInterface $logRepository) {}

    /**
     * Método para filtrar logs com base nos filtros fornecidos.
     *
     * @param array $filters Um array contendo os filtros.
     * @return bool
     */
    public function getFilteredLogs(array $filters): bool
    {
        try {
            $data = $this->logRepository->getFilteredLogs($filters);

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Logs listados com sucesso!');
            $this->setData($data);

            return true;
        } catch (Exception $e) {
            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao listar logs. Tente novamente mais tarde.');
            $this->setError($e->getMessage());
            $this->saveLog($filters);

            return false;
        }
    }
}
