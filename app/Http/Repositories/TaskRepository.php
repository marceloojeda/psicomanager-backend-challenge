<?php

namespace App\Http\Repositories;

use App\Http\Repositories\Interfaces\ITaskRepository;
use App\Http\Services\CacheService;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskRepository implements ITaskRepository
{
    private CacheService $cacheService;
    private int $perPage = 100;
    private int $page = 1;

    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;

    }

    public function findAll(Request $request): LengthAwarePaginator
    {
        $userId = $request->get('userId') ?? null;
        $perPage = $request->get('per_page') ?? $this->perPage;
        $page = $request->get('page') ?? $this->page;

        $cacheKey = "tasks_page_{$page}_perPage_{$perPage}";

        if ($userId) {
            $cacheTag = "tasks_user_{$userId}";
            return $this->cacheService->remember($cacheTag, $cacheKey, 10, function () use ($userId, $perPage, $page) {
                return Task::where('user_id', $userId)->paginate($perPage, ['*'], 'page', $page);
            });
        }

        return $this->cacheService->remember('tasks_global', 'all_tasks', 10, function () use ($perPage, $page) {
            return Task::paginate($perPage, ['*'], 'page', $page);
        });
    }

    public function findById(int $taskId): Task
    {
        return Task::findOrFail($taskId);
    }

    public function transferTasks(int $userId, int $adminId): array
    {
        $tasks = Task::select('id')->where('user_id', $userId)->get();
        Task::where('user_id', $userId)->update(['user_id' => $adminId]);
        $this->cacheService->forgetByTag("tasks_user_{$userId}");
        $this->cacheService->forgetByTag("tasks_user_{$adminId}");
        $this->cacheService->forget('tasks_global', 'all_tasks');
        
        return $tasks->toArray();
    }
}