<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\JsonResponse;

class ServiceResponse
{
    // Status da resposta, padrão é "Expectation Failed" (417)
    private int $status = Response::HTTP_EXPECTATION_FAILED;

    // Mensagem padrão
    private string $message = 'Erro na requisição';

    // Atributo para armazenar o erro específico, se houver
    private string $error = '';

    // Dados enviados na resposta
    private array $data = [];

    // Instancia de resource para formatar dados
    private string $resourceClass = '';

    // Se na resposta o $data precisa precisa retornar ou somente os dados formatados
    private bool $data_from_collection = true;
    /**
     * Pega o codigo de erro.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Define codigo de erro.
     *
     * @param int $status
     * @return void
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * Pega a mensagem do usuário.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Define a mensagem para o usuário.
     *
     * @param string $message
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Pega os dados.
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->getDataFromCollection() === true) {
            return $this->getCollection();
        }

        return $this->data;
    }

    /**
     * Define os dados.
     *
     * @param array $data
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Mensagem de erro na requisição.
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Mensagem de erro.
     *
     * @param string $error
     * @return void
     */
    public function setError(string $error): void
    {
        Log::error($error);
        $this->error = $error;
    }

    /**
     * Retornar collection com dados formatados.
     *
     * @return bool
     */
    public function getDataFromCollection(): bool
    {
        return $this->data_from_collection;
    }

    /**
     * Se é pra retornar collection com dados formatados.
     *
     * @param bool $data_from_collection
     * @return void
     */
    public function setDataFromCollection(bool $data_from_collection): void
    {
        $this->data_from_collection = $data_from_collection;
    }

    /**
     * Configura dados formatados do resource.
     *
     * @param JsonResource $resource
     * @return void
     */
    public function setResource(string $resourceClass): void
    {
        if (is_subclass_of($resourceClass, JsonResource::class) === false) {
            Log::error("O recurso deve ser uma instância de JsonResource.");
        }

        $this->resourceClass = $resourceClass;
    }

    /**
     * Pega dados formatados do resource.
     *
     * @return array
     */
    public function getCollection(): array
    {
        if (
            empty($this->resourceClass) === false &&
            is_subclass_of($this->resourceClass, JsonResource::class
        ) === true) {
            $isList = array_filter($this->data, fn($item) => is_object($item)) !== []
                || array_filter($this->data, fn($item) => is_array($item)) !== [];

            if ($isList) {
                $collection = $this->resourceClass::collection((array) $this->data);
            } else {
                $collection = new $this->resourceClass((array) $this->data);
            }

            return (array) $collection->resolve();
        }

        if (empty($this->data) === false) {
            return $this->data;
        }

        return [];
    }

    /**
     * Retornar resposta.
     *
     * @return array
     */
    public function getResponse(): array
    {
        return [
            'message' => $this->getMessage(),
            'data' => $this->getData(),
            'error' => $this->getError(),
            'status' => $this->getStatus()
        ];
    }

    /**
     * Retornar resposta em Json.
     *
     * @return JsonResponse
     */
    public function getJsonResponse(): JsonResponse
    {
        $data = $this->getResponse();
        return response()->json($data, $data['status']);
    }
}
