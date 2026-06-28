<?php

namespace App\Domains\Auth\Repositories;

use App\Models\User;

interface AuthRepositoryInterface
{
    public function findUserByEmail(string $email): ?User;

    public function findUserByUsername(string $username): ?User;

    public function findUserById(int $id): ?User;
}
