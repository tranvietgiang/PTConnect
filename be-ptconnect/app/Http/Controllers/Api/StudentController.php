<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $students = Student::query()
            ->with('classroom:id,name,grade_level')
            ->when($request->filled('classroom_id'), fn ($query) => $query->where('classroom_id', $request->integer('classroom_id')))
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = trim((string) $request->input('keyword'));

                $query->where(function ($inner) use ($keyword): void {
                    $inner->where('full_name', 'like', "%{$keyword}%")
                        ->orWhere('student_code', 'like', "%{$keyword}%");
                });
            })
            ->orderBy('full_name')
            ->get()
            ->map(fn (Student $student): array => $this->serialize($student));

        return $this->success('Students retrieved.', $students->all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'student_code' => ['required', 'string', 'max:50', Rule::unique('students', 'student_code')],
            'full_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $student = Student::query()->create([
            'classroom_id' => $validated['classroom_id'],
            'student_code' => trim($validated['student_code']),
            'full_name' => trim($validated['full_name']),
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'address' => $validated['address'] ?? null,
            'status' => 'studying',
        ]);

        return $this->success('Student created.', $this->serialize($student->load('classroom')), 201);
    }

    public function show(Student $student): JsonResponse
    {
        return $this->success('Student retrieved.', $this->serialize($student->load('classroom')));
    }

    private function serialize(Student $student): array
    {
        return [
            'id' => $student->id,
            'student_code' => $student->student_code,
            'full_name' => $student->full_name,
            'classroom_id' => $student->classroom_id,
            'class_name' => $student->classroom?->name,
            'date_of_birth' => $student->date_of_birth?->toDateString(),
            'address' => $student->address,
            'status' => $student->status,
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
