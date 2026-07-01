<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = [
        'created_by',
        'classroom_id',
        'grade_level',
        'title',
        'description',
        'due_date',
        'attachment_path',
        'attachment_name',
        'attachment_mime',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
        ];
    }
}
