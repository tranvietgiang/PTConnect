<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ParentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $query = Classroom::query()
            ->withCount('students')
            ->with('academicYear:id,name')
            ->orderBy('grade_level')
            ->orderBy('name');

        if ($user->role === 'parent') {
            $query->whereIn('id', $this->parentClassroomIds($user->id));
        } elseif (in_array($user->role, ['teacher', 'assistant'], true)) {
            $query->whereHas('users', fn ($inner) => $inner->where('users.id', $user->id));
        } elseif ($user->role !== 'admin') {
            return $this->error('Forbidden.', 403);
        }

        $classes = $query->get()->map(fn (Classroom $classroom): array => $this->serialize($classroom));

        return $this->success('Classes retrieved.', $classes->all());
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->attributes->get('auth_user')?->role !== 'admin') {
            return $this->error('Forbidden.', 403);
        }

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
        $request = request();
        $user = $request->attributes->get('auth_user');

        if (! $this->canAccessClass($request, $classroom)) {
            return $this->error('Forbidden.', 403);
        }

        $classroom->load(['academicYear:id,name', 'students'])->loadCount('students');
        $students = $classroom->students;

        if ($user->role === 'parent') {
            $students = $students->whereIn('id', $this->parentStudentIds($user->id));
            $classroom->students_count = $students->count();
        }

        return $this->success('Class retrieved.', [
            ...$this->serialize($classroom),
            'students' => $students->values()->map(fn ($student): array => [
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

    private function canAccessClass(Request $request, Classroom $classroom): bool
    {
        $user = $request->attributes->get('auth_user');

        if ($user->role === 'admin') {
            return true;
        }

        if (in_array($user->role, ['teacher', 'assistant'], true)) {
            return $classroom->users()->where('users.id', $user->id)->exists();
        }

        if ($user->role === 'parent') {
            return in_array($classroom->id, $this->parentClassroomIds($user->id), true);
        }

        return false;
    }

    private function parentClassroomIds(int $userId): array
    {
        return ParentProfile::query()
            ->where('user_id', $userId)
            ->with('student:id,classroom_id')
            ->get()
            ->pluck('student.classroom_id')
            ->filter()
            ->unique()
            ->values()
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function parentStudentIds(int $userId): array
    {
        return ParentProfile::query()
            ->where('user_id', $userId)
            ->pluck('student_id')
            ->unique()
            ->values()
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => null,
        ], $status);
    }
}
