<?php

namespace App\Repositories;

use App\Models\Course;

class CourseRepository extends Repository
{
    protected function model(): string
    {
        return Course::class;
    }
}
