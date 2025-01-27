<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

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
            'status' => $this->getStatus(),
        ];
    }
}
