<?php

namespace Tests\Unit\Http\Services;

use App\Http\Repositories\Interfaces\ITaskRepository;
use App\Http\Resources\TaskResource;
use App\Http\Services\TaskService;
use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    private TaskService $taskService;
    private ITaskRepository $taskRepository;

    protected function setUp(): void
    {
        $this->taskRepository = $this->createMock(ITaskRepository::class);
        $this->taskService = new TaskService($this->taskRepository);
    }

    public function testFindAllTasks()
    {
        $tasks = new Collection([
            ['name' => 'Task 1', 'description' => 'Description 1'],
            ['name' => 'Task 2', 'description' => 'Description 2']
        ]);

        $paginator = new LengthAwarePaginator(
            $tasks,
            $tasks->count(),
            10,
            1
        );

        $this->taskRepository->expects($this->once())
        ->method('findAll')
        ->willReturn($paginator);

        $result = $this->taskService->findAll(new Request());
        $this->assertCount(2, $result->items());
    }

    public function testFindTaskById()
    {
        $task = new Task(['description' => 'Description 1']);
        $task->id = 1;

        $this->taskRepository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($task);

        $result = $this->taskService->findById(1);

        $this->assertInstanceOf(TaskResource::class, $result);
        $this->assertEquals('Description 1', $result->description);
    }

}