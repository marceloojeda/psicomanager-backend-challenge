<?php

namespace App\Domain\Entities;

/**
 * Classe que representa um usuário.
 *
 * Esta classe é usada para representar um usuário com suas propriedades principais,
 * como o ID, nome, email, data de criação e suas tarefas associadas.
 */
class UserEntity {

    /**
     * Construtor da classe UserEntity.
     *
     * @param int $id O ID único do usuário.
     * @param string $name O nome do usuário.
     * @param string $email O endereço de e-mail do usuário.
     * @param string $createdAt A data de criação do usuário (em formato de string, como 'Y-m-d H:i:s').
     * @param array $tasks Lista de tarefas associadas ao usuário. Por padrão, é um array vazio.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $createdAt,
        public array $tasks = []
    ) {}
}
