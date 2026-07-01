<?php

namespace App\Repositories;

use App\Models\StudentEnrollment;
use App\Models\StudentProfile;

class StudentProfileRepository extends Repository
{
    protected function model(): string
    {
        return StudentProfile::class;
    }

    public function findByUserId(int $userId): ?StudentProfile
    {
        return StudentProfile::where('user_id', $userId)->first();
    }

    public function findByStudentCode(string $code): ?StudentProfile
    {
        return StudentProfile::where('student_code', $code)->first();
    }

    public function getLastStudentCode(string $prefix): ?StudentProfile
    {
        return StudentProfile::where('student_code', 'like', "{$prefix}%")
            ->orderBy('student_code', 'desc')
            ->first();
    }

    public function getActiveStudentsByClassroom(int $classroomId)
    {
        $ids = StudentEnrollment::where('classroom_id', $classroomId)
            ->where('status', 'active')
            ->pluck('student_id');

        return StudentProfile::whereIn('id', $ids)->get();
    }
}
