<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findById(string $username): ?User
    {
        return User::where('user_id', $username)->first();
    }
}
