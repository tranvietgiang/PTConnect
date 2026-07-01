<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AssistantAssignment;
use App\Models\Classroom;
use App\Models\ParentProfile;
use App\Models\StudentEnrollment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClassroomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $query = Classroom::query()
            ->with('course:id,name,grade_level,start_date,end_date,status')
            ->withCount([
                'studentEnrollments as students_count' => fn ($inner) => $inner->where('status', StudentEnrollment::STATUS_ACTIVE),
            ])
            ->orderBy('name');

        if ($user->isStudent()) {
            $query->whereHas('studentEnrollments.studentProfile', fn ($inner) => $inner->where('user_id', $user->id));
        } elseif ($user->isTeacher() || $user->isAssistant()) {
            if ($user->isTeacher()) {
                $query->where('teacher_id', $user->id);
            } else {
                $query->whereHas('assistantAssignments', fn ($inner) => $inner
                    ->where('assistant_id', $user->id)
                    ->where('status', AssistantAssignment::STATUS_ACTIVE));
            }
        } elseif (! $user->isAdmin()) {
            return $this->error('Forbidden.', 403);
        }

        $classes = $query->get()->map(fn (Classroom $classroom): array => $this->serialize($classroom));

        return $this->success('Classes retrieved.', $classes->all());
    }

    public function store(Request $request): JsonResponse
    {
        if (! $request->attributes->get('auth_user')?->isAdmin()) {
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
            'academic_year_id' => ['sometimes', 'integer', 'exists:academic_years,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_lessons' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'upcoming'])],
        ]);

        $classroom = Classroom::query()->create([
            'academic_year_id' => $validated['academic_year_id'] ?? $academicYear->id,
            'teacher_id' => $validated['teacher_id'] ?? null,
            'name' => trim($validated['name']),
            'grade_level' => $validated['grade_level'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_lessons' => $validated['total_lessons'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'status' => $validated['status'] ?? 'active',
        ]);

        return $this->success('Class created.', $this->serialize($classroom->load('academicYear')->loadCount('students')), 201);
    }

    public function update(Request $request, Classroom $classroom): JsonResponse
    {
        if (! $request->attributes->get('auth_user')?->isAdmin()) {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('classrooms', 'name')
                    ->where('academic_year_id', $classroom->academic_year_id)
                    ->ignore($classroom->id),
            ],
            'grade_level' => ['required', 'integer', Rule::in([10, 11, 12])],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_lessons' => ['required', 'integer', 'min:1', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive', 'upcoming'])],
        ]);

        $classroom->update([
            'name' => trim($validated['name']),
            'grade_level' => $validated['grade_level'],
            'teacher_id' => $validated['teacher_id'] ?? $classroom->teacher_id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_lessons' => $validated['total_lessons'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? $classroom->is_active,
            'status' => $validated['status'] ?? $classroom->status,
        ]);

        return $this->success('Class updated.', $this->serialize($classroom->refresh()->load('academicYear')->loadCount('students')));
    }

    public function show(Classroom $classroom): JsonResponse
    {
        $request = request();
        $user = $request->attributes->get('auth_user');

        if (! $this->canAccessClass($request, $classroom)) {
            return $this->error('Forbidden.', 403);
        }

        $classroom->load([
            'course:id,name,grade_level,start_date,end_date,status',
            'studentEnrollments.studentProfile',
        ])->loadCount([
            'studentEnrollments as students_count' => fn ($inner) => $inner->where('status', StudentEnrollment::STATUS_ACTIVE),
        ]);
        $students = $classroom->studentEnrollments
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->pluck('studentProfile')
            ->filter();

        if ($user->isStudent()) {
            $students = $students->where('user_id', $user->id);
            $classroom->students_count = $students->count();
        }

        return $this->success('Class retrieved.', [
            ...$this->serialize($classroom),
            'students' => $students->values()->map(fn ($student): array => [
                'id' => $student->id,
                'student_code' => $student->student_code,
                'full_name' => $student->full_name,
                'status' => 'studying',
            ])->all(),
        ]);
    }

    private function serialize(Classroom $classroom): array
    {
        $classroom->loadMissing('course:id,name,grade_level,start_date,end_date,status');
        $course = $classroom->course;

        $classroom->loadMissing('teacher:id,name,email');

        return [
            'id' => $classroom->id,
            'course_id' => $classroom->course_id,
            'course_name' => $course?->name,
            'teacher_id' => $classroom->teacher_id,
            'teacher_name' => $classroom->teacher?->name,
            'name' => $classroom->name,
            'grade_level' => $classroom->grade_level ?? $course?->grade_level,
            'start_date' => ($classroom->start_date ?? $course?->start_date)?->toDateString(),
            'end_date' => ($classroom->end_date ?? $course?->end_date)?->toDateString(),
            'total_lessons' => $classroom->total_lessons ?? null,
            'description' => $classroom->description ?? null,
            'academic_year_id' => $classroom->academic_year_id,
            'academic_year' => $classroom->academicYear?->name,
            'students_count' => $classroom->students_count ?? 0,
            'is_active' => $classroom->status === 'active',
            'status' => $classroom->status ?? 'active',
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

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher() || $user->isAssistant()) {
            if ($user->isTeacher()) {
                return (int) $classroom->teacher_id === (int) $user->id;
            }

            return $classroom->assistantAssignments()
                ->where('assistant_id', $user->id)
                ->where('status', AssistantAssignment::STATUS_ACTIVE)
                ->exists();
        }

        if ($user->isStudent()) {
            return $classroom->studentEnrollments()
                ->whereHas('studentProfile', fn ($inner) => $inner->where('user_id', $user->id))
                ->exists();
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
