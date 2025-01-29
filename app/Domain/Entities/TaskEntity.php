<?php

namespace App\Domain\Entities;

class TaskEntity {
    public function __construct(
        public int $id,
        public int $userId,
        public string $description,
        public string $status,
        public string $createdAt
    ) {}
}
