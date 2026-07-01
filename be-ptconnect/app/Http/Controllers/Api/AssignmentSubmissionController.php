<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AssignmentSubmissionRepository;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentSubmissionController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService,
        protected AssignmentSubmissionRepository $submissionRepo,
    ) {}

    public function store(int $assignmentId, Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:20480']);

        $submission = $this->assignmentService->submitAssignment(
            $assignmentId,
            $request->user()->id,
            $request->file('file'),
        );

        return response()->json([
            'message' => 'Đã nộp bài thành công.',
            'data' => $submission,
        ], 201);
    }

    public function download(int $id)
    {
        return $this->assignmentService->downloadSubmission($id);
    }

    public function grade(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'score' => 'nullable|numeric|min:0|max:999.99',
            'teacher_comment' => 'nullable|string|max:2000',
        ]);

        $submission = $this->submissionRepo->findOrFail($id);
        $this->submissionRepo->update($submission, [
            ...$validated,
            'status' => 'graded',
        ]);

        return response()->json([
            'message' => 'Đã chấm điểm bài nộp.',
            'data' => $submission,
        ]);
    }
}
