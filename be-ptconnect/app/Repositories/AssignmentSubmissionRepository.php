<?php

namespace App\Repositories;

use App\Models\AssignmentSubmission;

class AssignmentSubmissionRepository extends Repository
{
    protected function model(): string
    {
        return AssignmentSubmission::class;
    }

    public function updateOrCreate(array $attributes, array $values): AssignmentSubmission
    {
        return AssignmentSubmission::updateOrCreate($attributes, $values);
    }
}
