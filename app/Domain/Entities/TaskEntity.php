<?php

namespace App\Domain\Entities;

/**
 * Classe que representa uma tarefa.
 *
 * Esta classe é usada para representar uma tarefa com suas propriedades principais,
 * como o ID da tarefa, ID do usuário associado, descrição, status e data de criação.
 */
class TaskEntity {

    /**
     * Construtor da classe TaskEntity.
     *
     * @param int $id O ID único da tarefa.
     * @param int $userId O ID do usuário que está associado à tarefa.
     * @param string $description A descrição da tarefa.
     * @param string $status O status da tarefa (por exemplo, "pendente", "concluída").
     * @param string $createdAt A data de criação da tarefa (em formato de string, como 'Y-m-d H:i:s').
     */
    public function __construct(
        public int $id,
        public int $userId,
        public string $description,
        public string $status,
        public string $createdAt
    ) {}
}
