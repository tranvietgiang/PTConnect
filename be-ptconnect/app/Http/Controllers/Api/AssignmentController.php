<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\EmailLog;
use App\Models\Student;
use App\Models\StudentProfile;
use App\Support\AccessControl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssignmentController extends Controller
{
    private const ALLOWED_FILE_TYPES = 'pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,zip,rar,7z,txt,csv';

    public function index(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $query = Assignment::query()
            ->with(['classroom:id,name,course_id', 'classroom.course:id,name,grade_level', 'submissions.student:id,full_name'])
            ->orderByDesc('created_at');

        if ($user->isStudent()) {
            return $this->success('Assignments retrieved.', $this->parentAssignments($query, $user->id));
        }

        if ($user->isTeacher() || $user->isAssistant()) {
            $assignedClassroomIds = $this->assignedClassroomIds($user);

            $query->whereIn('classroom_id', $assignedClassroomIds);
        } elseif (! $user->isAdmin()) {
            return $this->error('Forbidden.', 403);
        }

        return $this->success(
            'Assignments retrieved.',
            $query->get()->map(fn(Assignment $assignment): array => $this->serialize($assignment))->all(),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        if (! $user->isAdmin() && ! $user->isTeacher()) {
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
                'extensions:'.self::ALLOWED_FILE_TYPES,
            ],
        ], [
            'attachment_file.extensions' => 'File đính kèm phải thuộc một trong các định dạng: '.self::ALLOWED_FILE_TYPES.'.',
            'attachment_file.max' => 'File đính kèm không được vượt quá 10MB.',
        ]);

        if (empty($validated['classroom_id']) && empty($validated['grade_level'])) {
            return $this->error('Vui lòng chọn lớp hoặc khối.', 422);
        }

        if ($user->isTeacher()) {
            if (empty($validated['classroom_id'])) {
                return $this->error('Giáo viên chỉ được giao bài cho lớp được phân công.', 403);
            }

            if (! in_array((int) $validated['classroom_id'], $this->assignedClassroomIds($user), true)) {
                return $this->error('Forbidden.', 403);
            }

            $validated['grade_level'] = null;
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

        if (! $user->isStudent()) {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'submitted_file' => [
                'required',
                'file',
                'max:10240',
                'extensions:'.self::ALLOWED_FILE_TYPES,
            ],
        ], [
            'submitted_file.required' => 'Vui lòng chọn file bài nộp.',
            'submitted_file.file' => 'File bài nộp không hợp lệ.',
            'submitted_file.extensions' => 'File bài nộp phải thuộc một trong các định dạng: '.self::ALLOWED_FILE_TYPES.'.',
            'submitted_file.max' => 'File bài nộp không được vượt quá 10MB.',
        ]);

        $student = $this->parentStudents($user->id)->firstWhere('id', (int) $validated['student_id']);

        if (! $student || ! $this->assignmentMatchesStudent($assignment->loadMissing('classroom'), $student)) {
            return $this->error('Forbidden.', 403);
        }

        if ($this->isOverdue($assignment)) {
            return $this->error('Đã quá hạn nộp bài, bạn không thể nộp thêm.', 422);
        }

        $file = $request->file('submitted_file');
        $submittedAt = now();

        $existing = AssignmentSubmission::query()
            ->where('assignment_id', $assignment->id)
            ->where('student_id', $student->id)
            ->first();
        $oldFilePath = $existing?->submitted_file_path;
        $newFilePath = $file->store('assignments/submissions');

        $submission = AssignmentSubmission::query()->updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'student_id' => $student->id,
            ],
            [
                'submitted_file_path' => $newFilePath,
                'submitted_file_name' => $file->getClientOriginalName(),
                'submitted_file_mime' => $file->getClientMimeType(),
                'submitted_at' => $submittedAt,
                'status' => 'submitted',
            ],
        );

        if ($oldFilePath && $oldFilePath !== $newFilePath) {
            Storage::disk('local')->delete($oldFilePath);
        }

        return $this->success('Submission uploaded.', $this->serializeSubmission($submission), 201);
    }

    public function downloadSubmission(Request $request, AssignmentSubmission $submission): JsonResponse|BinaryFileResponse
    {
        $user = $request->attributes->get('auth_user');

        if ($user->isStudent()) {
            $ownedStudentIds = $this->parentStudents($user->id)->pluck('id');

            if (! $ownedStudentIds->contains($submission->student_id)) {
                return $this->error('Forbidden.', 403);
            }
        } elseif ($user->isTeacher() || $user->isAssistant()) {
            $submission->loadMissing('assignment');

            if (
                ! $submission->assignment->classroom_id
                || ! in_array((int) $submission->assignment->classroom_id, $this->assignedClassroomIds($user), true)
            ) {
                return $this->error('Forbidden.', 403);
            }
        } elseif (! $user->isAdmin()) {
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

    public function gradeSubmission(Request $request, AssignmentSubmission $submission): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        $submission->loadMissing(['assignment', 'student.classroom']);

        if (! $this->canGradeSubmission($user, $submission)) {
            return $this->error('Forbidden.', 403);
        }

        if ($request->filled('score')) {
            $request->merge([
                'score' => str_replace(',', '.', trim((string) $request->input('score'))),
            ]);
        }

        $validated = $request->validate([
            'score' => ['nullable', 'regex:/^(10([.]0{1,2})?|[0-9]([.][0-9]{1,2})?)$/'],
            'teacher_comment' => ['nullable', 'string', 'max:2000'],
        ], [
            'score.regex' => 'Điểm phải từ 0 đến 10 và chỉ được nhập tối đa 2 chữ số thập phân.',
            'teacher_comment.max' => 'Nhận xét không được vượt quá 2000 ký tự.',
        ]);

        $submission->update([
            'score' => $validated['score'] ?? null,
            'teacher_comment' => $validated['teacher_comment'] ?? null,
        ]);

        return $this->success('Submission graded.', $this->serializeSubmission($submission->refresh()->load('student')));
    }

    public function sendScoreEmail(Request $request, AssignmentSubmission $submission): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        $submission->loadMissing(['assignment.classroom', 'student.classroom']);

        if (! $this->canGradeSubmission($user, $submission)) {
            return $this->error('Forbidden.', 403);
        }

        if ($submission->score === null) {
            return $this->error('Bài tập chưa có điểm, không thể gửi email.', 422);
        }

        $parentEmail = $this->getSubmissionParentEmail($submission);
        if (! $parentEmail) {
            return $this->error('Không tìm thấy email phụ huynh.', 422);
        }

        try {
            Mail::send(new \App\Mail\ScoreNotification(
                studentName: $submission->student?->full_name ?? 'N/A',
                className: $submission->assignment?->classroom?->name ?? $submission->student?->classroom?->name ?? 'N/A',
                assignmentTitle: $submission->assignment?->title ?? 'N/A',
                score: $submission->score !== null ? (string) $submission->score : null,
                comment: $submission->teacher_comment,
            ));

            $submission->update([
                'email_status' => 'sent',
                'score_emailed_at' => now(),
                'email_sent_by' => $user->id,
                'email_error' => null,
            ]);

            EmailLog::query()->create([
                'recipient_email' => $parentEmail,
                'recipient_name' => $submission->student?->full_name,
                'subject' => 'PTConnect - Thông báo điểm bài tập',
                'content' => "Điểm bài tập {$submission->assignment?->title}: {$submission->score}",
                'type' => 'score',
                'status' => 'sent',
                'sent_at' => now(),
                'related_type' => 'assignment_submission',
                'related_id' => $submission->id,
            ]);

            return $this->success('Email điểm đã được gửi.', $this->serializeSubmission($submission->refresh()));
        } catch (\Throwable $e) {
            $submission->update([
                'email_status' => 'failed',
                'email_error' => $e->getMessage(),
            ]);

            return $this->error('Gửi email thất bại: ' . $e->getMessage(), 500);
        }
    }

    public function sendBulkScoreEmail(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        $validated = $request->validate([
            'submission_ids' => ['required', 'array', 'min:1'],
            'submission_ids.*' => ['integer', Rule::exists('assignment_submissions', 'id')],
        ]);

        $submissions = AssignmentSubmission::query()
            ->with(['assignment.classroom', 'student.classroom'])
            ->whereIn('id', $validated['submission_ids'])
            ->get();

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($submissions as $submission) {
            if (! $this->canGradeSubmission($user, $submission)) {
                $failed++;
                $errors[] = "Submission #{$submission->id}: Forbidden.";
                continue;
            }

            if ($submission->score === null) {
                $failed++;
                $errors[] = "Submission #{$submission->id}: Chưa có điểm.";
                continue;
            }

            $parentEmail = $this->getSubmissionParentEmail($submission);
            if (! $parentEmail) {
                $failed++;
                $errors[] = "Submission #{$submission->id}: Không tìm thấy email PH.";
                continue;
            }

            try {
                Mail::send(new \App\Mail\ScoreNotification(
                    studentName: $submission->student?->full_name ?? 'N/A',
                    className: $submission->assignment?->classroom?->name ?? $submission->student?->classroom?->name ?? 'N/A',
                    assignmentTitle: $submission->assignment?->title ?? 'N/A',
                    score: $submission->score !== null ? (string) $submission->score : null,
                    comment: $submission->teacher_comment,
                ));

                $submission->update([
                    'email_status' => 'sent',
                    'score_emailed_at' => now(),
                    'email_sent_by' => $user->id,
                    'email_error' => null,
                ]);

                EmailLog::query()->create([
                    'recipient_email' => $parentEmail,
                    'recipient_name' => $submission->student?->full_name,
                    'subject' => 'PTConnect - Thông báo điểm bài tập',
                    'content' => "Điểm bài tập {$submission->assignment?->title}: {$submission->score}",
                    'type' => 'score',
                    'status' => 'sent',
                    'sent_at' => now(),
                    'related_type' => 'assignment_submission',
                    'related_id' => $submission->id,
                ]);

                $sent++;
            } catch (\Throwable $e) {
                $submission->update([
                    'email_status' => 'failed',
                    'email_error' => $e->getMessage(),
                ]);

                $failed++;
                $errors[] = "Submission #{$submission->id}: {$e->getMessage()}";
            }
        }

        return $this->success('Email đã được gửi.', [
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ]);
    }

    private function getSubmissionParentEmail(AssignmentSubmission $submission): ?string
    {
        $student = $submission->student;
        if (! $student) {
            return null;
        }

        $parent = $student->parents()->first();
        if ($parent?->email) {
            return $parent->email;
        }

        $profile = StudentProfile::query()
            ->where('student_code', $student->student_code)
            ->first();

        return $profile?->parent_email;
    }

    private function parentAssignments($query, int $userId): array
    {
        $students = $this->parentStudents($userId);
        $classroomIds = $students->pluck('classroom_id')->unique()->values();
        $gradeLevels = $students->pluck('classroom.course.grade_level')->filter()->unique()->values();

        $assignments = $query
            ->where('status', 'published')
            ->where(function ($inner) use ($classroomIds, $gradeLevels): void {
                $inner->whereIn('classroom_id', $classroomIds)
                    ->orWhereIn('grade_level', $gradeLevels);
            })
            ->get();

        return $assignments->flatMap(function (Assignment $assignment) use ($students): array {
            return $students
                ->filter(fn(Student $student): bool => $this->assignmentMatchesStudent($assignment, $student))
                ->map(fn(Student $student): array => $this->serialize($assignment, $student))
                ->all();
        })->values()->all();
    }

    private function canAccessAssignment(Request $request, Assignment $assignment): bool
    {
        $user = $request->attributes->get('auth_user');

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeacher() || $user->isAssistant()) {
            return $assignment->classroom_id
                && in_array((int) $assignment->classroom_id, $this->assignedClassroomIds($user), true);
        }

        if ($user->isStudent()) {
            return $this->parentStudents($user->id)
                ->contains(fn(Student $student): bool => $this->assignmentMatchesStudent($assignment->loadMissing('classroom'), $student));
        }

        return false;
    }

    private function canGradeSubmission($user, AssignmentSubmission $submission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isTeacher() && ! $user->isAssistant()) {
            return false;
        }

        $studentClassroomId = $submission->student?->classroom_id;

        return $studentClassroomId
            && in_array((int) $studentClassroomId, $this->assignedClassroomIds($user), true);
    }

    private function parentStudents(int $userId): Collection
    {
        $user = request()->attributes->get('auth_user');
        $studentIds = $user ? AccessControl::legacyStudentIdsForUser($user) : [];

        return Student::query()
            ->with('classroom.course')
            ->whereIn('id', $studentIds)
            ->get()
            ->values();
    }

    private function assignedClassroomIds($user): array
    {
        return AccessControl::assignedClassroomIds($user);
    }

    private function assignmentMatchesStudent(Assignment $assignment, Student $student): bool
    {
        return ((int) $assignment->classroom_id === (int) $student->classroom_id)
            || ($assignment->grade_level && (int) $assignment->grade_level === (int) $student->classroom?->course?->grade_level);
    }

    private function isOverdue(Assignment $assignment): bool
    {
        return $assignment->due_date
            && now()->toDateString() > $assignment->due_date->toDateString();
    }

    private function assignmentStudents(Assignment $assignment): Collection
    {
        return Student::query()
            ->with('classroom:id,name,course_id', 'classroom.course:id,name,grade_level')
            ->where(function ($query) use ($assignment): void {
                $hasClassroom = (bool) $assignment->classroom_id;

                if ($hasClassroom) {
                    $query->where('classroom_id', $assignment->classroom_id);
                }

                if ($assignment->grade_level) {
                    $gradeScope = fn($classroom) => $classroom->whereHas(
                        'course',
                        fn ($course) => $course->where('grade_level', $assignment->grade_level),
                    );

                    if ($hasClassroom) {
                        $query->orWhereHas('classroom', $gradeScope);
                    } else {
                        $query->whereHas('classroom', $gradeScope);
                    }
                }
            })
            ->orderBy('full_name')
            ->get();
    }

    private function serializeSubmissionStatuses(Assignment $assignment): array
    {
        $assignment->loadMissing('submissions.student');
        $submissions = $assignment->submissions->keyBy('student_id');

        return $this->assignmentStudents($assignment)
            ->map(fn(Student $student): array => $this->serializeStudentSubmission(
                $assignment,
                $student,
                $submissions->get($student->id),
            ))
            ->all();
    }

    private function serializeStudentSubmission(
        Assignment $assignment,
        Student $student,
        ?AssignmentSubmission $submission,
    ): array {
        $submitted = (bool) $submission;

        return [
            'id' => $submission?->id,
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'student_name' => $student->full_name,
            'class_name' => $student->classroom?->name,
            'file_name' => $submission?->submitted_file_name,
            'submitted_at' => $submission?->submitted_at?->toDateTimeString(),
            'status' => $submitted ? 'submitted' : 'not_submitted',
            'status_label' => $submitted ? 'Đã nộp' : 'Chưa nộp',
            'score' => $submission?->score,
            'teacher_comment' => $submission?->teacher_comment,
        ];
    }

    private function serialize(Assignment $assignment, ?Student $student = null): array
    {
        $submission = $student
            ? $assignment->submissions->firstWhere('student_id', $student->id)
            : null;
        $isOverdue = $this->isOverdue($assignment);
        $submissionStatuses = $student ? [] : $this->serializeSubmissionStatuses($assignment);
        $submittedCount = collect($submissionStatuses)
            ->where('status', 'submitted')
            ->count();
        $submissionStatus = $submission ? 'submitted' : 'not_submitted';

        return [
            'row_key' => $student ? "{$assignment->id}-{$student->id}" : (string) $assignment->id,
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'classroom_id' => $assignment->classroom_id,
            'class_name' => $student?->classroom?->name ?? $assignment->classroom?->name,
            'grade_level' => $assignment->grade_level,
            'due_date' => $assignment->due_date?->toDateString(),
            'is_overdue' => $isOverdue,
            'can_submit' => ! $isOverdue,
            'status' => $assignment->status,
            'has_attachment' => (bool) $assignment->attachment_path,
            'attachment_name' => $assignment->attachment_name,
            'student_id' => $student?->id,
            'student_name' => $student?->full_name,
            'submission_status' => $student ? $submissionStatus : null,
            'submission_status_label' => $student ? ($submission ? 'Đã nộp' : 'Chưa nộp') : null,
            'submitted_count' => $submittedCount,
            'student_count' => count($submissionStatuses),
            'submission' => $submission ? $this->serializeSubmission($submission) : null,
            'submissions' => $submissionStatuses,
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
            'status' => 'submitted',
            'status_label' => 'Đã nộp',
            'stored_status' => $submission->status,
            'score' => $submission->score,
            'teacher_comment' => $submission->teacher_comment,
            'email_status' => $submission->email_status ?? 'not_sent',
            'email_status_label' => match ($submission->email_status ?? 'not_sent') {
                'sent' => 'Đã gửi',
                'failed' => 'Gửi lỗi',
                default => 'Chưa gửi',
            },
            'score_emailed_at' => $submission->score_emailed_at?->toDateTimeString(),
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
