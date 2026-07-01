<?php

namespace App\Repositories;

use App\Models\TeacherProfile;

class TeacherProfileRepository extends Repository
{
    protected function model(): string
    {
        return TeacherProfile::class;
    }

    public function findByUserId(int $userId): ?TeacherProfile
    {
        return TeacherProfile::where('user_id', $userId)->first();
    }
}
