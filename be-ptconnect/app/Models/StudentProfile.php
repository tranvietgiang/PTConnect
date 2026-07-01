<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'student_code',
        'full_name',
        'email',
        'parent_email',
        'high_school',
        'cccd',
        'date_of_birth',
        'phone',
        'address',
        'parent_phone',
        'parent_name',
        'parent_relationship',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
        ];
    }
}
