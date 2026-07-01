<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'student_code',
        'full_name',
        'student_email',
        'parent_email',
        'high_school_name',
        'cccd',
        'date_of_birth',
        'student_phone',
        'address',
        'parent_phone',
        'parent_full_name',
        'parent_relation',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id');
    }

    public function enrollments(): HasMany
    {
        return $this->studentEnrollments();
    }
}
