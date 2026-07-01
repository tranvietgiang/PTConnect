<?php

namespace App\Repositories;

use App\Models\ScoreColumn;

class ScoreColumnRepository extends Repository
{
    protected function model(): string
    {
        return ScoreColumn::class;
    }
}
