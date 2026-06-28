<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'recipient_email',
        'recipient_name',
        'subject',
        'content',
        'type',
        'status',
        'error_message',
        'sent_at',
        'related_type',
        'related_id',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
