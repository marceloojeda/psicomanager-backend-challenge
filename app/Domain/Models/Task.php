<?php

namespace App\Domain\Models;

class Task
{
    private $id;
    private $userId;
    private $description;

    public function __construct($id, $userId, $description)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->description = $description;
    }

    // Métodos de acesso aos atributos
    public function getId()
    {
        return $this->id;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getDescription()
    {
        return $this->description;
    }
}
