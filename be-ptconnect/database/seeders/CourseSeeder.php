<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        Course::create([
            'academic_year_id' => 1,
            'name' => 'Sinh học khối 10 - Hè 2026',
            'grade_level' => 10,
            'is_active' => true,
        ]);

        Course::create([
            'academic_year_id' => 1,
            'name' => 'Sinh học khối 11 - Năm học 2025-2026',
            'grade_level' => 11,
            'is_active' => true,
        ]);

        Course::create([
            'academic_year_id' => 1,
            'name' => 'Sinh học khối 12 - Ôn thi THPT',
            'grade_level' => 12,
            'is_active' => true,
        ]);
    }
}
