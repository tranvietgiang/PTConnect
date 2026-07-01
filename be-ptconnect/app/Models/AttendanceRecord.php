<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'status',
        'late_minutes',
        'email_status',
        'email_sent_at',
        'email_error',
        'created_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'email_sent_at' => 'datetime',
            'late_minutes' => 'integer',
        ];
    }
}
