<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ScoreRecordRepository;
use App\Services\ScoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoreController extends Controller
{
    public function __construct(
        protected ScoreService $scoreService,
        protected ScoreRecordRepository $recordRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->recordRepo->newQuery();

        if ($request->filled('classroom_id')) {
            $columnIds = \App\Models\ScoreColumn::where('classroom_id', $request->input('classroom_id'))
                ->pluck('id');
            $query->whereIn('score_column_id', $columnIds);
        }

        if ($request->filled('score_column_id')) {
            $query->where('score_column_id', $request->input('score_column_id'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        $records = $query->paginate($request->input('per_page', 50));

        $records->getCollection()->transform(function ($record) {
            $column = \App\Models\ScoreColumn::find($record->score_column_id);
            $student = \App\Models\StudentProfile::find($record->student_id);

            return [
                'id' => $record->id,
                'score_column_id' => $record->score_column_id,
                'student_id' => $record->student_id,
                'student_name' => $student?->full_name,
                'student_code' => $student?->student_code,
                'column_name' => $column?->name,
                'score' => $record->score,
                'email_status' => $record->email_status,
                'note' => $record->note,
                'created_at' => $record->created_at,
            ];
        });

        return response()->json($records);
    }

    public function report(Request $request): JsonResponse
    {
        $result = $this->scoreService->getReport($request->all());

        return response()->json([
            'data' => $result,
        ]);
    }

    public function saveRecords(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'score_column_id' => 'required|exists:score_columns,id',
            'records' => 'required|array|min:1',
            'records.*.student_id' => 'required|exists:student_profiles,id',
            'records.*.score' => 'nullable|numeric|min:0|max:999.99',
            'records.*.note' => 'nullable|string|max:500',
        ]);

        $result = $this->scoreService->saveRecords(
            $validated['score_column_id'],
            $validated['records'],
            $request->user()->id
        );

        return response()->json([
            'message' => 'Đã lưu điểm thành công.',
            'data' => $result,
        ]);
    }
}
