<?php

namespace App\Http\Repositories;

use App\Http\Repositories\Interfaces\ITaskRepository;
use App\Models\Task;

class TaskRepository implements ITaskRepository
{
    public function findAll()
    {
        return Task::all();
    }

    public function findById($taskId)
    {
        return Task::where('id', $taskId)->firstOrFail();
    }

    public function findByUser($userId)
    {
        return Task::where('user_id', $userId)->get();
    }

    public function persist($request)
    {
        return Task::create($request->all());
    }

    public function transferTasks($userId, $adminId)
    {
        $tasks = Task::select('id')->where('user_id', $userId)->get();
        Task::where('user_id', $userId)->update(['user_id' => $adminId]);
        return $tasks->toArray();
    }

    public function delete($taskId)
    {
        $task = Task::where('id', $taskId)->firstOrFail();
        $task->delete();
    }
}