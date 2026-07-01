<?php

namespace App\Repositories;

use App\Models\StudentEnrollment;

class StudentEnrollmentRepository extends Repository
{
    protected function model(): string
    {
        return StudentEnrollment::class;
    }

    public function findActiveByClassroom(int $classroomId)
    {
        return StudentEnrollment::where('classroom_id', $classroomId)
            ->where('status', 'active')
            ->get();
    }

    public function studentBelongsToClassroom(int $studentId, int $classroomId): bool
    {
        return StudentEnrollment::where('student_id', $studentId)
            ->where('classroom_id', $classroomId)
            ->where('status', 'active')
            ->exists();
    }
}
