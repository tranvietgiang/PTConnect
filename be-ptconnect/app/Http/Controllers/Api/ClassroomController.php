<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    public function index(): JsonResponse
    {
        $classes = Classroom::query()
            ->withCount('students')
            ->with('academicYear:id,name')
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get()
            ->map(fn (Classroom $classroom): array => $this->serialize($classroom));

        return $this->success('Classes retrieved.', $classes->all());
    }

    public function store(Request $request): JsonResponse
    {
        $academicYear = AcademicYear::query()->where('is_active', true)->first()
            ?? AcademicYear::query()->create([
                'name' => now()->year.'-'.now()->addYear()->year,
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
                'is_active' => true,
            ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms', 'name')->where('academic_year_id', $academicYear->id),
            ],
            'grade_level' => ['required', 'integer', Rule::in([10, 11, 12])],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $classroom = Classroom::query()->create([
            'academic_year_id' => $academicYear->id,
            'name' => trim($validated['name']),
            'grade_level' => $validated['grade_level'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return $this->success('Class created.', $this->serialize($classroom->load('academicYear')->loadCount('students')), 201);
    }

    public function show(Classroom $classroom): JsonResponse
    {
        $classroom->load(['academicYear:id,name', 'students'])->loadCount('students');

        return $this->success('Class retrieved.', [
            ...$this->serialize($classroom),
            'students' => $classroom->students->map(fn ($student): array => [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'full_name' => $student->full_name,
                'status' => $student->status,
            ])->all(),
        ]);
    }

    private function serialize(Classroom $classroom): array
    {
        return [
            'id' => $classroom->id,
            'name' => $classroom->name,
            'grade_level' => $classroom->grade_level,
            'description' => $classroom->description,
            'academic_year' => $classroom->academicYear?->name,
            'students_count' => $classroom->students_count ?? 0,
            'is_active' => $classroom->is_active,
        ];
    }

    private function success(string $message, array $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
