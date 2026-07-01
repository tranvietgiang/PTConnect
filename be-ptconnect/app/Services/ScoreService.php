<?php

namespace App\Services;

use App\Models\ScoreColumn;
use App\Repositories\ScoreColumnRepository;
use App\Repositories\ScoreRecordRepository;
use App\Repositories\StudentProfileRepository;
use Illuminate\Support\Facades\DB;

class ScoreService
{
    public function __construct(
        protected ScoreColumnRepository $columnRepo,
        protected ScoreRecordRepository $recordRepo,
        protected StudentProfileRepository $studentRepo,
        protected EmailNotificationService $emailService,
    ) {}

    public function saveRecords(int $scoreColumnId, array $records, int $userId): array
    {
        return DB::transaction(function () use ($scoreColumnId, $records, $userId) {
            $this->columnRepo->findOrFail($scoreColumnId);
            $updatedRecords = [];

            foreach ($records as $record) {
                $scoreRecord = $this->recordRepo->updateOrCreate(
                    [
                        'score_column_id' => $scoreColumnId,
                        'student_id' => $record['student_id'],
                    ],
                    [
                        'score' => $record['score'] ?? null,
                        'note' => $record['note'] ?? null,
                        'created_by' => $userId,
                        'email_status' => 'pending',
                    ]
                );

                $updatedRecords[] = $scoreRecord;
            }

            return $updatedRecords;
        });
    }

    public function getReport(array $filters): array
    {
        $columns = $this->getFilteredColumns($filters);

        $columnIds = $columns->pluck('id');
        $allRecords = $this->recordRepo->newQuery()
            ->whereIn('score_column_id', $columnIds)
            ->get()
            ->groupBy('student_id');

        $studentIds = $allRecords->keys()->toArray();
        $students = $this->studentRepo->newQuery()
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');

        $report = [];
        foreach ($allRecords as $studentId => $records) {
            $student = $students->get($studentId);

            $scores = [];
            foreach ($records as $record) {
                $column = $columns->firstWhere('id', $record->score_column_id);
                $scores[] = [
                    'column_name' => $column?->name ?? 'N/A',
                    'score' => $record->score,
                    'weight' => $column?->weight,
                    'max_score' => $column?->max_score,
                ];
            }

            $totalWeight = 0;
            $weightedSum = 0;
            foreach ($scores as $score) {
                if ($score['score'] !== null && $score['weight']) {
                    $weightedSum += $score['score'] * $score['weight'];
                    $totalWeight += $score['weight'];
                }
            }

            $report[] = [
                'student_id' => $studentId,
                'student_name' => $student?->full_name ?? 'N/A',
                'student_code' => $student?->student_code ?? 'N/A',
                'scores' => $scores,
                'average' => $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0,
            ];
        }

        return array_values($report);
    }

    private function getFilteredColumns(array $filters)
    {
        $query = $this->columnRepo->newQuery();

        if (!empty($filters['classroom_id'])) {
            $query->where('classroom_id', $filters['classroom_id']);
        }

        return $query->get();
    }

    public function sendScoreEmails(int $columnId, ?array $studentIds = null): array
    {
        $records = $this->recordRepo->findPendingByColumn($columnId, $studentIds);

        $sent = 0;

        foreach ($records as $record) {
            $success = $this->emailService->sendScoreEmail($record);
            if ($success) {
                $sent++;
            }
        }

        return ['sent' => $sent, 'total' => count($records)];
    }
}
