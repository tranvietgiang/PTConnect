<?php

namespace App\Repositories;

use App\Models\AssistantProfile;

class AssistantProfileRepository extends Repository
{
    protected function model(): string
    {
        return AssistantProfile::class;
    }

    public function findByUserId(int $userId): ?AssistantProfile
    {
        return AssistantProfile::where('user_id', $userId)->first();
    }
}
