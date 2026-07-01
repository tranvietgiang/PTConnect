<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Classroom extends Model
{
    protected $fillable = [
        'course_id',
        'academic_year_id',
        'teacher_id',
        'assistant_id',
        'name',
        'grade_level',
        'start_date',
        'end_date',
        'total_lessons',
        'study_days',
        'start_time',
        'end_time',
        'max_students',
        'status',
        'is_active',
        'description',
        'assistant_access_locked_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'study_days' => 'array',
            'is_active' => 'boolean',
            'assistant_access_locked_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
