<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AssignmentRepository;
use App\Repositories\ClassroomRepository;
use App\Repositories\UserRepository;
use App\Services\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService,
        protected AssignmentRepository $assignmentRepo,
        protected ClassroomRepository $classroomRepo,
        protected UserRepository $userRepo,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $this->assignmentRepo->newQuery();

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->input('grade_level'));
        }

        $assignments = $query->get()->map(function ($assignment) {
            $classroom = $this->classroomRepo->find($assignment->classroom_id);
            $creator = $this->userRepo->find($assignment->created_by);

            return [
                'id' => $assignment->id,
                'classroom_id' => $assignment->classroom_id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'due_date' => $assignment->due_date,
                'status' => $assignment->status,
                'classroom_name' => $classroom?->name,
                'creator_name' => $creator?->email,
            ];
        });

        return response()->json([
            'data' => $assignments,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'file' => 'nullable|file|max:20480',
        ]);

        $assignment = $this->assignmentService->createAssignment(
            $validated,
            $request->user()->id,
            $request->file('file'),
        );

        return response()->json([
            'message' => 'Đã tạo bài tập thành công.',
            'data' => $assignment,
        ], 201);
    }

    public function downloadAttachment(int $id)
    {
        return $this->assignmentService->downloadAttachment($id);
    }
}
