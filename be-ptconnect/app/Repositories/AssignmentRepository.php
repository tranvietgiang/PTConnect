<?php

namespace App\Repositories;

use App\Models\Assignment;

class AssignmentRepository extends Repository
{
    protected function model(): string
    {
        return Assignment::class;
    }
}
