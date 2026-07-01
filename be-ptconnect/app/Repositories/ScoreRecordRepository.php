<?php

namespace App\Repositories;

use App\Models\ScoreRecord;

class ScoreRecordRepository extends Repository
{
    protected function model(): string
    {
        return ScoreRecord::class;
    }

    public function updateOrCreate(array $attributes, array $values): ScoreRecord
    {
        return ScoreRecord::updateOrCreate($attributes, $values);
    }

    public function findPendingByColumn(int $columnId, ?array $studentIds = null)
    {
        $query = ScoreRecord::where('score_column_id', $columnId)
            ->whereNotNull('score');

        if ($studentIds !== null) {
            $query->whereIn('student_id', $studentIds);
        }

        return $query->get();
    }

    public function findPendingByStudent(int $studentId, ?int $columnId = null)
    {
        $query = ScoreRecord::where('student_id', $studentId)
            ->whereIn('email_status', ['pending', 'failed']);

        if ($columnId) {
            $query->where('score_column_id', $columnId);
        }

        return $query->get();
    }
}
