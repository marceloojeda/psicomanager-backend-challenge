<?php

namespace App\Http\Services;

use App\Http\Repositories\Interfaces\IUserRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserService
{
    private IUserRepository $userRepository;

    public function __construct(IUserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getUsers(Request $request)
    {
        
        if ($request->has('name') || $request->has('id')) {
            return $this->userRepository->findByFilter($request);
        }

        return response()->json($this->userRepository->findAll());
    }

    public function store(Request $request)
    {
        return response()->json($this->userRepository->persist($request));
    }

    public function delete($userId)
    {
        $this->userRepository->delete($userId);
        return response("Usuario excluido com sucesso", Response::HTTP_ACCEPTED);
    }
}