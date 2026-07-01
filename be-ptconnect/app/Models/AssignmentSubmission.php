<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentSubmission extends Model
{
    protected $fillable = [
        'assignment_id',
        'student_id',
        'submitted_file_path',
        'submitted_file_name',
        'submitted_file_mime',
        'submitted_at',
        'status',
        'score',
        'teacher_comment',
        'email_status',
        'score_emailed_at',
        'email_sent_by',
        'email_error',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'score_emailed_at' => 'datetime',
        ];
    }
}
