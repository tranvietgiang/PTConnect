<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreRecord extends Model
{
    protected $fillable = [
        'score_column_id',
        'student_id',
        'score',
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
        ];
    }
}
