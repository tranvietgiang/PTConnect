<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreColumn extends Model
{
    protected $fillable = [
        'classroom_id',
        'name',
        'max_score',
        'weight',
        'test_date',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'test_date' => 'date',
        ];
    }
}
