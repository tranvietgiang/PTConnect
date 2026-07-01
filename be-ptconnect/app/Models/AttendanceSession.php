<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = [
        'classroom_id',
        'attendance_date',
        'lesson_number',
        'session_name',
        'start_time',
        'end_time',
        'status',
        'created_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
        ];
    }
}
