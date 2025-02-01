<?php

namespace App\Http\Repositories\Interfaces;

interface IUserRepository
{
    public function findAll();
    public function persist($request);
    public function delete($userId);
}