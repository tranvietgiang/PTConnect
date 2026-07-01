<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_LATE = 'late';

    public const EMAIL_NOT_SENT = 'not_sent';
    public const EMAIL_SENT = 'sent';
    public const EMAIL_FAILED = 'failed';

    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'status',
        'late_minutes',
        'note',
        'email_status',
        'emailed_at',
        'email_sent_by',
    ];

    protected function casts(): array
    {
        return [
            'late_minutes' => 'integer',
            'emailed_at' => 'datetime',
        ];
    }

    public function attendanceSession(): BelongsTo
    {
        return $this->belongsTo(AttendanceSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
