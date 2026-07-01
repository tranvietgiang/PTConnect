<?php

namespace App\Infrastructure\Repositories;

use App\Domains\Auth\Repositories\AuthRepositoryInterface;
use App\Models\User;

class EloquentAuthRepository implements AuthRepositoryInterface
{
    public function findUserByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findUserByUsername(string $username): ?User
    {
        return User::query()->where('username', $username)->first();
    }

    public function findUserById(int $id): ?User
    {
        return User::query()->find($id);
    }
}
