<?php

namespace App\Repositories;

use App\Models\Classroom;

class ClassroomRepository extends Repository
{
    protected function model(): string
    {
        return Classroom::class;
    }

    public function findByName(string $name): ?Classroom
    {
        return Classroom::where('name', $name)->first();
    }
}
