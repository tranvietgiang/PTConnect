<?php

namespace App\Repositories;

use App\Models\AttendanceRecord;

class AttendanceRecordRepository extends Repository
{
    protected function model(): string
    {
        return AttendanceRecord::class;
    }

    public function updateOrCreate(array $attributes, array $values): AttendanceRecord
    {
        return AttendanceRecord::updateOrCreate($attributes, $values);
    }

    public function findPendingEmailRecords()
    {
        return AttendanceRecord::where('email_status', 'pending')->get();
    }

    public function findAbsentLatePending(int $sessionId)
    {
        return AttendanceRecord::where('attendance_session_id', $sessionId)
            ->whereIn('status', ['absent', 'late'])
            ->where('email_status', 'not_required')
            ->get();
    }
}
