<?php

namespace Database\Seeders;

use App\Models\Classroom;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        Classroom::create([
            'course_id' => 1,
            'academic_year_id' => 1,
            'teacher_id' => 3,
            'assistant_id' => 4,
            'name' => '10A1',
            'grade_level' => 10,
            'total_lessons' => 30,
            'status' => 'active',
            'is_active' => true,
        ]);

        Classroom::create([
            'course_id' => 1,
            'academic_year_id' => 1,
            'teacher_id' => 3,
            'assistant_id' => 4,
            'name' => '10A2',
            'grade_level' => 10,
            'total_lessons' => 30,
            'status' => 'active',
            'is_active' => true,
        ]);

        Classroom::create([
            'course_id' => 2,
            'academic_year_id' => 1,
            'teacher_id' => 3,
            'assistant_id' => null,
            'name' => '11A1',
            'grade_level' => 11,
            'total_lessons' => 30,
            'status' => 'active',
            'is_active' => true,
        ]);

        Classroom::create([
            'course_id' => 3,
            'academic_year_id' => 1,
            'teacher_id' => 3,
            'assistant_id' => null,
            'name' => '12A1',
            'grade_level' => 12,
            'total_lessons' => 40,
            'status' => 'upcoming',
            'is_active' => true,
        ]);
    }
}
