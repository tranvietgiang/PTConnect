<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssistantProfile extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
    ];
}
