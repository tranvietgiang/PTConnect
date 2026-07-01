<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'grade_level',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'grade_level' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }

    public function studentEnrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function assistantAssignments(): HasMany
    {
        return $this->hasMany(AssistantAssignment::class);
    }
}
