<?php

namespace App\Http\Repositories\Interfaces;

interface ITaskRepository
{
    public function findAll();
    public function findByUser($userId);
    public function transferTasks($userId, $adminId);
    public function findById($taskId);
}