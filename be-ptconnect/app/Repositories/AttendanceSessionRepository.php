<?php

namespace App\Repositories;

use App\Models\AttendanceSession;

class AttendanceSessionRepository extends Repository
{
    protected function model(): string
    {
        return AttendanceSession::class;
    }

    public function findCurrent(int $classroomId, string $date, int $lessonNumber): ?AttendanceSession
    {
        return AttendanceSession::where('classroom_id', $classroomId)
            ->where('attendance_date', $date)
            ->where('lesson_number', $lessonNumber)
            ->first();
    }

    public function updateOrCreate(array $attributes, array $values): AttendanceSession
    {
        return AttendanceSession::updateOrCreate($attributes, $values);
    }
}
