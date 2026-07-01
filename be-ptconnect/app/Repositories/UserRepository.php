<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends Repository
{
    protected function model(): string
    {
        return User::class;
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findActiveByEmail(string $email): ?User
    {
        return User::where('email', $email)->where('is_active', true)->first();
    }

    public function emailExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function updateLastLogin(int $userId): void
    {
        User::where('id', $userId)->update(['last_login_at' => now()]);
    }
}
