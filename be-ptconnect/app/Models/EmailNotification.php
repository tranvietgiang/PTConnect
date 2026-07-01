<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailNotification extends Model
{
    protected $fillable = [
        'student_id',
        'recipient_email',
        'recipient_name',
        'subject',
        'content',
        'type',
        'reference_type',
        'reference_id',
        'status',
        'sent_at',
        'error_message',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
