<?php

namespace App\Domain\Interfaces;

use App\Domain\Entities\UserEntity;

/**
 * Interface que define os métodos necessários para a manipulação dos usuários.
 *
 * Esta interface serve como contrato para a implementação de repositórios que lidam com a recuperação, criação e exclusão de usuários.
 * Ela define os métodos que devem ser implementados para acessar e manipular dados de usuários no sistema.
 */
interface UserRepositoryInterface {

    /**
     * Obtém um usuário pelo seu ID.
     *
     * @param int $userId O ID do usuário a ser recuperado.
     * @return UserEntity|null Retorna o usuário correspondente ou null se não for encontrado.
     */
    public function getUserRepository(int $userId): ?UserEntity;

    /**
     * Obtém uma lista de usuários filtrados de acordo com os critérios fornecidos.
     *
     * @param array $filters Array associativo com os filtros que serão aplicados à busca dos usuários.
     * @return UserEntity[] Lista de usuários que atendem aos filtros fornecidos.
     */
    public function getFilteredUsersRepository(array $filters): array;

    /**
     * Cria um novo usuário no sistema.
     *
     * @param array $data Dados do usuário a ser criado. O array deve conter informações como nome, e-mail, etc.
     * @return UserEntity O usuário recém-criado.
     */
    public function createUserRepository(array $data): UserEntity;

    /**
     * Exclui um usuário do sistema pelo seu ID.
     *
     * @param int $id O ID do usuário a ser excluído.
     * @return UserEntity Retorna UserEntity.
     */
    public function deleteUserRepository(int $id): UserEntity;
}
