<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ParentProfile;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssignmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $query = Assignment::query()
            ->with(['classroom:id,name,grade_level', 'submissions.student:id,full_name'])
            ->orderByDesc('created_at');

        if ($user->role === 'parent') {
            $students = $this->parentStudents($user->id);
            $classroomIds = $students->pluck('classroom_id')->unique()->values();
            $gradeLevels = $students->pluck('classroom.grade_level')->filter()->unique()->values();

            $assignments = $query
                ->where('status', 'published')
                ->where(function ($inner) use ($classroomIds, $gradeLevels): void {
                    $inner->whereIn('classroom_id', $classroomIds)
                        ->orWhereIn('grade_level', $gradeLevels);
                })
                ->get();

            $data = $assignments->flatMap(function (Assignment $assignment) use ($students): array {
                return $students
                    ->filter(fn (Student $student): bool => $this->assignmentMatchesStudent($assignment, $student))
                    ->map(fn (Student $student): array => $this->serialize($assignment, $student))
                    ->all();
            })->values()->all();

            return $this->success('Assignments retrieved.', $data);
        }

        return $this->success(
            'Assignments retrieved.',
            $query->get()->map(fn (Assignment $assignment): array => $this->serialize($assignment))->all(),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (! in_array($user->role, ['admin', 'teacher'], true)) {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
            'grade_level' => ['nullable', 'integer', Rule::in([10, 11, 12])],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['draft', 'published'])],
            'attachment_file' => [
                'nullable',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png',
            ],
        ]);

        if (empty($validated['classroom_id']) && empty($validated['grade_level'])) {
            return $this->error('Vui lòng chọn lớp hoặc khối.', 422);
        }

        $fileData = [];

        if ($request->hasFile('attachment_file')) {
            $file = $request->file('attachment_file');
            $fileData = [
                'attachment_path' => $file->store('assignments/attachments'),
                'attachment_name' => $file->getClientOriginalName(),
                'attachment_mime' => $file->getClientMimeType(),
            ];
        }

        $assignment = Assignment::query()->create([
            'created_by' => $user->id,
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'classroom_id' => $validated['classroom_id'] ?? null,
            'grade_level' => $validated['grade_level'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'status' => $validated['status'] ?? 'published',
            ...$fileData,
        ]);

        return $this->success('Assignment created.', $this->serialize($assignment->load('classroom')), 201);
    }

    public function downloadAttachment(Request $request, Assignment $assignment): JsonResponse|BinaryFileResponse
    {
        if (! $assignment->attachment_path || ! Storage::disk('local')->exists($assignment->attachment_path)) {
            return $this->error('File not found.', 404);
        }

        if (! $this->canAccessAssignment($request, $assignment)) {
            return $this->error('Forbidden.', 403);
        }

        return response()->download(
            Storage::disk('local')->path($assignment->attachment_path),
            $assignment->attachment_name,
        );
    }

    public function submit(Request $request, Assignment $assignment): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if ($user->role !== 'parent') {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'submitted_file' => [
                'required',
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,jpg,jpeg,png,zip',
            ],
        ]);

        $student = $this->parentStudents($user->id)->firstWhere('id', (int) $validated['student_id']);

        if (! $student || ! $this->assignmentMatchesStudent($assignment->loadMissing('classroom'), $student)) {
            return $this->error('Forbidden.', 403);
        }

        $file = $request->file('submitted_file');
        $submittedAt = now();
        $status = $assignment->due_date && $submittedAt->toDateString() > $assignment->due_date->toDateString()
            ? 'late'
            : 'submitted';

        $existing = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();

        if ($existing?->submitted_file_path) {
            Storage::disk('local')->delete($existing->submitted_file_path);
        }

        $submission = AssignmentSubmission::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'submitted_file_path' => $file->store('assignments/submissions'),
                'submitted_file_name' => $file->getClientOriginalName(),
                'submitted_file_mime' => $file->getClientMimeType(),
                'submitted_at' => $submittedAt,
                'status' => $status,
            ],
        );

        return $this->success('Submission uploaded.', $this->serializeSubmission($submission), 201);
    }

    public function downloadSubmission(Request $request, AssignmentSubmission $submission): JsonResponse|BinaryFileResponse
    {
        $user = $request->attributes->get('auth_user');

        if ($user->role === 'parent') {
            $ownedStudentIds = $this->parentStudents($user->id)->pluck('id');

            if (! $ownedStudentIds->contains($submission->student_id)) {
                return $this->error('Forbidden.', 403);
            }
        } elseif (! in_array($user->role, ['admin', 'teacher'], true)) {
            return $this->error('Forbidden.', 403);
        }

        if (! Storage::disk('local')->exists($submission->submitted_file_path)) {
            return $this->error('File not found.', 404);
        }

        return response()->download(
            Storage::disk('local')->path($submission->submitted_file_path),
            $submission->submitted_file_name,
        );
    }

    private function canAccessAssignment(Request $request, Assignment $assignment): bool
    {
        $user = $request->attributes->get('auth_user');

        if (in_array($user->role, ['admin', 'teacher', 'assistant'], true)) {
            return true;
        }

        return $this->parentStudents($user->id)
            ->contains(fn (Student $student): bool => $this->assignmentMatchesStudent($assignment->loadMissing('classroom'), $student));
    }

    private function parentStudents(int $userId): Collection
    {
        return ParentProfile::query()
            ->where('user_id', $userId)
            ->with('students.classroom')
            ->get()
            ->flatMap(fn (ParentProfile $parent): Collection => $parent->students)
            ->unique('id')
            ->values();
    }

    private function assignmentMatchesStudent(Assignment $assignment, Student $student): bool
    {
        return ((int) $assignment->classroom_id === (int) $student->classroom_id)
            || ($assignment->grade_level && (int) $assignment->grade_level === (int) $student->classroom?->grade_level);
    }

    private function serialize(Assignment $assignment, ?Student $student = null): array
    {
        $submission = $student
            ? $assignment->submissions->firstWhere('student_id', $student->id)
            : null;

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'classroom_id' => $assignment->classroom_id,
            'class_name' => $assignment->classroom?->name,
            'grade_level' => $assignment->grade_level,
            'due_date' => $assignment->due_date?->toDateString(),
            'status' => $assignment->status,
            'has_attachment' => (bool) $assignment->attachment_path,
            'attachment_name' => $assignment->attachment_name,
            'student_id' => $student?->id,
            'student_name' => $student?->full_name,
            'submission' => $submission ? $this->serializeSubmission($submission) : null,
            'submissions' => $student
                ? []
                : $assignment->submissions->map(fn (AssignmentSubmission $submission): array => $this->serializeSubmission($submission))->all(),
        ];
    }

    private function serializeSubmission(AssignmentSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'student_id' => $submission->student_id,
            'file_name' => $submission->submitted_file_name,
            'student_name' => $submission->student?->full_name,
            'submitted_at' => $submission->submitted_at?->toDateTimeString(),
            'status' => $submission->status,
            'score' => $submission->score,
            'teacher_comment' => $submission->teacher_comment,
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

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => null,
        ], $status);
    }
}
