<?php

namespace App\Domain\Entities;

/**
 * Classe que representa uma log.
 *
 * Esta classe é usada para representar um log com suas propriedades principais,
 */
class LogEntity {

    public function __construct(
        public int $id,
        public string $level,
        public string $message,
        public string $context,
        public string $userId,
        public string $ipAddress,
        public string $requestId,
        public string $createdAt
    ) {}
}
