<?php

namespace Tests\Unit\Http\Services;

use App\Http\Repositories\Interfaces\ITaskRepository;
use App\Http\Repositories\Interfaces\IUserRepository;
use App\Http\Resources\UserResource;
use App\Http\Services\LogService;
use App\Http\Services\UserService;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    private UserService $userService;
    private IUserRepository $userRepository;
    private ITaskRepository $taskRepository;
    private LogService $logService;

    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(IUserRepository::class);
        $this->taskRepository = $this->createMock(ITaskRepository::class);
        $this->logService = $this->createMock(LogService::class);
        $this->userService = new UserService($this->userRepository, $this->taskRepository, $this->logService);
    }

    public function testCreateUser()
    {
        $data = [
            'name' => 'John Doe',
            'email' => '5H5hK@example.com',
            'password' => '12345678',
        ];

        $user = new User($data);

        $this->userRepository->expects($this->once())->method('persist')->willReturn($user);

        $request = Request::create('/users', 'POST', $data);

        $response = $this->userService->store($request);

        $this->assertInstanceOf(UserResource::class, $response);

        $responseArray = $response->toArray($request);

        $this->assertEquals($data['name'], $responseArray['name']);
        $this->assertEquals($data['email'], $responseArray['email']);
    }

    public function testFindAllUsers()
    {
        $john = ['name' => 'John Doe', 'email' => '5H5hK@example.com'];
        $jane = ['name' => 'Jane Doe', 'email' => 'j5H5hK@example.com'];

        $userJohn = new User($john);
        $userJane = new User($jane);

        $this->userRepository->method('findAll')->willReturn(new Collection([$userJohn, $userJane]));

        $request = Request::create('/users', 'GET');

        $users = $this->userService->getUsers($request);

        $this->assertInstanceOf(AnonymousResourceCollection::class, $users);

        $usersArray = $users->toArray($request);

        $this->assertCount(2, $usersArray);
        $this->assertEquals('John Doe', $usersArray[0]['name']);
        $this->assertEquals('5H5hK@example.com', $usersArray[0]['email']);
        $this->assertEquals('Jane Doe', $usersArray[1]['name']);
        $this->assertEquals('j5H5hK@example.com', $usersArray[1]['email']);
    }

    public function testFindAllUsersWithFilterByName()
    {
        $john = ['name' => 'John Doe', 'email' => '5H5hK@example.com'];

        $userJohn = new User($john);

        $request = Request::create('/users', 'GET', ['name' => 'John']);

        $this->userRepository->method('findByFilter')->willReturn(new Collection([$userJohn]));

        $users = $this->userService->getUsers($request);

        $this->assertInstanceOf(AnonymousResourceCollection::class, $users);

        $usersArray = $users->toArray($request);

        $this->assertCount(1, $usersArray);
        $this->assertEquals('John Doe', $usersArray[0]['name']);
        $this->assertEquals('5H5hK@example.com', $usersArray[0]['email']);
    }

    public function testFindAllUsersWithFilterById()
    {
        $john = ['name' => 'John Doe', 'email' => '5H5hK@example.com'];

        $userJohn = new User($john);
        $userJohn->id = 1;

        $request = Request::create('/users', 'GET', ['id' => 1]);

        $this->userRepository->method('findByFilter')->willReturn(new Collection([$userJohn]));

        $users = $this->userService->getUsers($request);

        $this->assertInstanceOf(AnonymousResourceCollection::class, $users);

        $usersArray = $users->toArray($request);

        $this->assertCount(1, $usersArray);
        $this->assertEquals('John Doe', $usersArray[0]['name']);
        $this->assertEquals('5H5hK@example.com', $usersArray[0]['email']);
    }

    public function testFindAllUsersEmpty()
    {
        $request = Request::create('/users', 'GET');
        $this->userRepository->method('findAll')->willReturn(new Collection());
        $this->expectExceptionMessage('Nenhum usuário encontrado');
        $this->userService->getUsers($request);
    }

    public function testFindUserById()
    {
        $john = ['name' => 'John Doe', 'email' => '5H5hK@example.com'];

        $user = new User($john);
        $user->id = 1;

        $this->userRepository->method('findById')->willReturn($user);
        $user = $this->userService->getUserById(1);

        $this->assertInstanceOf(UserResource::class, $user);
        $this->assertEquals(1, $user->id);
        $this->assertEquals($john['name'], $user->name);
        $this->assertEquals($john['email'], $user->email);
    }

    public function testFindUserByIdNotFound()
    {
        $this->userRepository->method('findById')->willReturn(null);
        $this->expectExceptionMessage('Usuário não encontrado');
        $this->userService->getUserById(1);
    }

    public function testDeleteUser()
    {
        $data = ['name' => 'John Doe', 'email' => '5H5hK@example.com'];
        $user = new User($data);
        $user->id = 1;

        $dataAdmin = ['name' => 'Admin', 'email' => 'admin@example.com', 'is_admin' => true];
        $admin = new User($dataAdmin);
        $admin->id = 2;

        $this->userRepository->expects($this->once())
            ->method('findById')
            ->with($user->id)
            ->willReturn($user);
        
        $this->userRepository->expects($this->once())
            ->method('findUserIsAdmin')
            ->willReturn($admin);

        $this->taskRepository->expects($this->once())
            ->method('transferTasks')
            ->with($user->id, $admin->id);

        $this->userRepository->expects($this->once())
            ->method('delete')
            ->with($user->id);

        $this->userService->delete(1);
    }

}