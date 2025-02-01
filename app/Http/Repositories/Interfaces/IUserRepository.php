<?php

namespace App\Http\Repositories\Interfaces;

interface IUserRepository
{
    public function findAll();
    public function findById($userId);
    public function findByFilter($request);
    public function findUserIsAdmin();
    public function persist($request);
    public function delete($userId);
}