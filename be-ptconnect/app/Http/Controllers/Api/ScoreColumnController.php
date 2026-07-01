<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Score\StoreScoreColumnRequest;
use App\Models\ScoreRecord;
use App\Repositories\ScoreColumnRepository;
use App\Repositories\StudentProfileRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScoreColumnController extends Controller
{
    public function __construct(
        protected ScoreColumnRepository $columnRepo,
        protected StudentProfileRepository $studentRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->columnRepo->newQuery();

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        $columns = $query->get()->map(function ($column) {
            $classroom = \App\Models\Classroom::find($column->classroom_id);
            return [
                'id' => $column->id,
                'classroom_id' => $column->classroom_id,
                'name' => $column->name,
                'max_score' => $column->max_score,
                'weight' => $column->weight,
                'test_date' => $column->test_date,
                'note' => $column->note,
                'classroom_name' => $classroom?->name,
            ];
        });

        return response()->json([
            'data' => $columns,
        ]);
    }

    public function store(StoreScoreColumnRequest $request): JsonResponse
    {
        $column = $this->columnRepo->create($request->validated());

        $students = $this->studentRepo->getActiveStudentsByClassroom($column->classroom_id);

        foreach ($students as $student) {
            ScoreRecord::create([
                'score_column_id' => $column->id,
                'student_id' => $student->id,
                'created_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'message' => 'Đã tạo cột điểm thành công.',
            'data' => $column,
        ], 201);
    }

    public function update(int $id, StoreScoreColumnRequest $request): JsonResponse
    {
        $column = $this->columnRepo->findOrFail($id);
        $this->columnRepo->update($column, $request->validated());

        return response()->json([
            'message' => 'Đã cập nhật cột điểm thành công.',
            'data' => $column,
        ]);
    }
}
