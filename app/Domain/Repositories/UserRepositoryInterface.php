<?php

namespace App\Domain\Repositories;

interface UserRepositoryInterface
{
    public function getAll();

    public function create($data);

    public function delete($userId);
}
