<?php

namespace App\Repositories;

use App\Models\AcademicYear;

class AcademicYearRepository extends Repository
{
    protected function model(): string
    {
        return AcademicYear::class;
    }
}
