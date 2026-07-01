<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Support\AccessControl;

class AttendanceController extends Controller
{
    public function sessions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['nullable', 'integer', Rule::exists('classrooms', 'id')],
            'grade_level' => ['nullable', 'integer', Rule::in([10, 11, 12])],
            'date' => ['nullable', 'date'],
        ]);

        $user = $request->attributes->get('auth_user');
        $query = AttendanceSession::query()
            ->with('classroom.course:id,name,grade_level,start_date,end_date')
            ->withCount([
                'attendanceRecords as present_count' => fn ($query) => $query->where('status', 'present'),
                'attendanceRecords as late_count' => fn ($query) => $query->where('status', 'late'),
                'attendanceRecords as absent_count' => fn ($query) => $query->where('status', 'absent'),
            ])
            ->orderByDesc('session_date')
            ->orderByDesc('lesson_number')
            ->orderByDesc('id');

        if (! $user?->isAdmin()) {
            if (! $user?->isTeacher() && ! $user?->isAssistant()) {
                return $this->error('Forbidden.', 403);
            }

            $query->whereIn('classroom_id', $this->assignedClassroomIds($user));
        }

        if (! empty($validated['classroom_id'])) {
            $query->where('classroom_id', $validated['classroom_id']);
        }

        if (! empty($validated['grade_level'])) {
            $query->whereHas('classroom.course', fn ($inner) => $inner->where('grade_level', $validated['grade_level']));
        }

        if (! empty($validated['date'])) {
            $query->whereDate('session_date', $validated['date']);
        }

        $sessions = $query->limit(200)->get()
            ->map(fn (AttendanceSession $session): array => $this->serializeManagedSession($session))
            ->all();

        return $this->success('Attendance sessions retrieved.', $sessions);
    }

    public function storeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'session_date' => ['nullable', 'date', 'required_without:attendance_date'],
            'attendance_date' => ['nullable', 'date', 'required_without:session_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'status' => ['nullable', Rule::in([AttendanceSession::STATUS_OPEN, AttendanceSession::STATUS_CLOSED])],
            'lesson_number' => ['nullable', 'integer', 'min:1'],
            'session_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $classroom = Classroom::query()->findOrFail($validated['classroom_id']);
        $sessionDate = $this->sessionDate($validated);
        $lessonNumber = (int) ($validated['lesson_number'] ?? 1);

        if (! $this->canManageSession($request, $classroom)) {
            return $this->error('Forbidden.', 403);
        }

        if (! $this->lessonNumberIsValid($classroom, $lessonNumber)) {
            return $this->error('Lesson number is outside the class lesson range.', 422);
        }

        if ($this->sessionExists($classroom->id, $sessionDate, $lessonNumber)) {
            return $this->error('Buổi học đã tồn tại cho lớp và ngày đã chọn.', 422);
        }

        $session = AttendanceSession::query()->create([
            'classroom_id' => $classroom->id,
            'session_date' => $sessionDate,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'status' => $validated['status'] ?? AttendanceSession::STATUS_OPEN,
            'lesson_number' => $lessonNumber,
            'session_name' => $validated['session_name'] ?? "Lesson {$lessonNumber}",
            'created_by' => $request->attributes->get('auth_user')->id,
            'note' => $validated['note'] ?? null,
        ]);

        $session->load('classroom.course:id,name,grade_level,start_date,end_date')
            ->loadCount([
                'attendanceRecords as present_count' => fn ($query) => $query->where('status', 'present'),
                'attendanceRecords as late_count' => fn ($query) => $query->where('status', 'late'),
                'attendanceRecords as absent_count' => fn ($query) => $query->where('status', 'absent'),
            ]);

        return $this->success('Attendance session created.', $this->serializeManagedSession($session), 201);
    }

    public function bulkStoreSessions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'start_date' => ['required', 'date'],
            'start_lesson_number' => ['required', 'integer', 'min:1'],
            'lesson_count' => ['required', 'integer', 'min:1', 'max:100'],
            'day_interval' => ['required', 'integer', 'min:0', 'max:30'],
            'session_name_prefix' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $classroom = Classroom::query()->findOrFail($validated['classroom_id']);
        $startLessonNumber = (int) $validated['start_lesson_number'];
        $lessonCount = (int) $validated['lesson_count'];
        $lastLessonNumber = $startLessonNumber + $lessonCount - 1;

        if (! $this->canManageSession($request, $classroom)) {
            return $this->error('Forbidden.', 403);
        }

        if (! $this->lessonNumberIsValid($classroom, $startLessonNumber)
            || ! $this->lessonNumberIsValid($classroom, $lastLessonNumber)) {
            return $this->error('Lesson range is outside the class lesson range.', 422);
        }

        $startDate = Carbon::parse($validated['start_date']);
        $dayInterval = (int) $validated['day_interval'];
        $prefix = trim($validated['session_name_prefix'] ?? '') ?: 'Lesson';
        $created = [];
        $skipped = [];

        for ($index = 0; $index < $lessonCount; $index++) {
            $lessonNumber = $startLessonNumber + $index;
            $attendanceDate = $startDate->copy()->addDays($index * $dayInterval)->toDateString();

            if ($this->sessionExists($classroom->id, $attendanceDate, $lessonNumber)) {
                $skipped[] = [
                    'session_date' => $attendanceDate,
                    'attendance_date' => $attendanceDate,
                    'lesson_number' => $lessonNumber,
                ];
                continue;
            }

            $session = AttendanceSession::query()->create([
                'classroom_id' => $classroom->id,
                'session_date' => $attendanceDate,
                'status' => AttendanceSession::STATUS_OPEN,
                'lesson_number' => $lessonNumber,
                'session_name' => "{$prefix} {$lessonNumber}",
                'created_by' => $request->attributes->get('auth_user')->id,
                'note' => $validated['note'] ?? null,
            ]);

            $session->load('classroom.course:id,name,grade_level,start_date,end_date')
                ->loadCount([
                    'attendanceRecords as present_count' => fn ($query) => $query->where('status', 'present'),
                    'attendanceRecords as late_count' => fn ($query) => $query->where('status', 'late'),
                    'attendanceRecords as absent_count' => fn ($query) => $query->where('status', 'absent'),
                ]);

            $created[] = $this->serializeManagedSession($session);
        }

        return $this->success('Attendance sessions created.', [
            'created_count' => count($created),
            'skipped_count' => count($skipped),
            'created' => $created,
            'skipped' => $skipped,
        ], 201);
    }

    public function showSession(AttendanceSession $session): JsonResponse
    {
        $session->loadMissing('classroom');

        if (! $this->canTakeAttendance(request(), $session->classroom)) {
            return $this->error('Forbidden.', 403);
        }

        $session->load([
            'classroom.students' => fn ($query) => $query->orderBy('full_name'),
            'classroom.course',
            'attendanceRecords.student',
        ])->loadCount([
            'attendanceRecords as present_count' => fn ($query) => $query->where('status', 'present'),
            'attendanceRecords as late_count' => fn ($query) => $query->where('status', 'late'),
            'attendanceRecords as absent_count' => fn ($query) => $query->where('status', 'absent'),
        ]);

        return $this->success('Attendance session retrieved.', [
            ...$this->serializeManagedSession($session),
            'records' => $this->serializeStudentRecords($session->classroom->students, $session),
        ]);
    }

    public function updateSession(Request $request, AttendanceSession $session): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'session_date' => ['nullable', 'date', 'required_without:attendance_date'],
            'attendance_date' => ['nullable', 'date', 'required_without:session_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after_or_equal:start_time'],
            'status' => ['nullable', Rule::in([AttendanceSession::STATUS_OPEN, AttendanceSession::STATUS_CLOSED])],
            'lesson_number' => ['nullable', 'integer', 'min:1'],
            'session_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $classroom = Classroom::query()->findOrFail($validated['classroom_id']);
        $sessionDate = $this->sessionDate($validated);
        $lessonNumber = (int) ($validated['lesson_number'] ?? 1);

        if (! $this->canManageSession($request, $classroom) || ! $this->canManageSession($request, $session->classroom)) {
            return $this->error('Forbidden.', 403);
        }

        if (! $this->lessonNumberIsValid($classroom, $lessonNumber)) {
            return $this->error('Lesson number is outside the class lesson range.', 422);
        }

        if ($this->sessionExists($classroom->id, $sessionDate, $lessonNumber, $session->id)) {
            return $this->error('Buổi học đã tồn tại cho lớp và ngày đã chọn.', 422);
        }

        $session->update([
            'classroom_id' => $classroom->id,
            'session_date' => $sessionDate,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
            'status' => $validated['status'] ?? $session->status,
            'lesson_number' => $lessonNumber,
            'session_name' => $validated['session_name'] ?? "Lesson {$lessonNumber}",
            'note' => $validated['note'] ?? null,
        ]);

        $session->refresh()
            ->load('classroom.course:id,name,grade_level,start_date,end_date')
            ->loadCount([
                'attendanceRecords as present_count' => fn ($query) => $query->where('status', 'present'),
                'attendanceRecords as late_count' => fn ($query) => $query->where('status', 'late'),
                'attendanceRecords as absent_count' => fn ($query) => $query->where('status', 'absent'),
            ]);

        return $this->success('Attendance session updated.', $this->serializeManagedSession($session));
    }

    public function destroySession(AttendanceSession $session): JsonResponse
    {
        $session->loadMissing('classroom');

        if (! $this->canManageSession(request(), $session->classroom)) {
            return $this->error('Forbidden.', 403);
        }

        $session->delete();

        return $this->success('Attendance session deleted.', []);
    }

    public function closeSession(AttendanceSession $session): JsonResponse
    {
        $session->loadMissing('classroom');

        if (! $this->canManageSession(request(), $session->classroom)) {
            return $this->error('Forbidden.', 403);
        }

        $session->update([
            'status' => AttendanceSession::STATUS_CLOSED,
        ]);

        $session->refresh()
            ->load('classroom.course:id,name,grade_level,start_date,end_date')
            ->loadCount([
                'attendanceRecords as present_count' => fn ($query) => $query->where('status', 'present'),
                'attendanceRecords as late_count' => fn ($query) => $query->where('status', 'late'),
                'attendanceRecords as absent_count' => fn ($query) => $query->where('status', 'absent'),
            ]);

        return $this->success('Attendance session closed.', $this->serializeManagedSession($session));
    }

    public function today(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'date' => ['nullable', 'date'],
            'lesson_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $classroom = Classroom::query()
            ->with(['students' => fn ($query) => $query->orderBy('full_name')])
            ->findOrFail($validated['classroom_id']);

        if (! $this->canTakeAttendance($request, $classroom)) {
            return $this->error('Forbidden.', 403);
        }

        $lessonNumber = (int) ($validated['lesson_number'] ?? 1);

        if (! $this->lessonNumberIsValid($classroom, $lessonNumber)) {
            return $this->error('Lesson number is outside the class lesson range.', 422);
        }

        $date = $validated['date'] ?? now()->toDateString();
        $session = AttendanceSession::query()
            ->where('classroom_id', $classroom->id)
            ->whereDate('session_date', $date)
            ->where('lesson_number', $lessonNumber)
            ->with('attendanceRecords.student')
            ->first();

        return $this->success('Attendance data retrieved.', [
            'classroom' => $this->serializeClassroom($classroom),
            'session' => $session ? $this->serializeSession($session) : null,
            'records' => $this->serializeStudentRecords($classroom->students, $session),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => ['required', 'integer', Rule::exists('classrooms', 'id')],
            'session_date' => ['nullable', 'date', 'required_without:attendance_date'],
            'attendance_date' => ['nullable', 'date', 'required_without:session_date'],
            'lesson_number' => ['nullable', 'integer', 'min:1'],
            'session_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'records.*.status' => ['required', Rule::in([
                AttendanceRecord::STATUS_PRESENT,
                AttendanceRecord::STATUS_LATE,
                AttendanceRecord::STATUS_ABSENT,
            ])],
            'records.*.late_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'records.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $classroom = Classroom::query()->with('students.parents')->findOrFail($validated['classroom_id']);
        $sessionDate = $this->sessionDate($validated);

        if (! $this->canTakeAttendance($request, $classroom)) {
            return $this->error('Forbidden.', 403);
        }

        $lessonNumber = (int) ($validated['lesson_number'] ?? 1);

        if (! $this->lessonNumberIsValid($classroom, $lessonNumber)) {
            return $this->error('Lesson number is outside the class lesson range.', 422);
        }

        $classStudentIds = $classroom->students->pluck('id')->map(fn ($id): int => (int) $id)->all();

        foreach ($validated['records'] as $record) {
            if (! in_array((int) $record['student_id'], $classStudentIds, true)) {
                return $this->error('Học sinh không thuộc lớp đã chọn.', 422);
            }
        }

        $session = AttendanceSession::query()
            ->where('classroom_id', $classroom->id)
            ->whereDate('session_date', $sessionDate)
            ->where('lesson_number', $lessonNumber)
            ->first();

        if ($session?->status === AttendanceSession::STATUS_CLOSED) {
            return $this->error('Attendance session is closed.', 422);
        }

        if ($session) {
            $session->update([
                'session_name' => $validated['session_name'] ?? "Lesson {$lessonNumber}",
                'note' => $validated['note'] ?? null,
            ]);
        } else {
            $session = AttendanceSession::query()->create([
                'classroom_id' => $classroom->id,
                'session_date' => $sessionDate,
                'status' => AttendanceSession::STATUS_OPEN,
                'lesson_number' => $lessonNumber,
                'session_name' => $validated['session_name'] ?? "Lesson {$lessonNumber}",
                'created_by' => $request->attributes->get('auth_user')->id,
                'note' => $validated['note'] ?? null,
            ]);
        }

        foreach ($validated['records'] as $record) {
            $status = $record['status'];
            $lateMinutes = $status === AttendanceRecord::STATUS_LATE ? (int) ($record['late_minutes'] ?? 0) : 0;

            AttendanceRecord::query()->updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'student_id' => $record['student_id'],
                ],
                [
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                    'note' => $record['note'] ?? null,
                    'email_status' => AttendanceRecord::EMAIL_NOT_SENT,
                    'emailed_at' => null,
                    'email_sent_by' => null,
                ],
            );
        }

        $session->load(['attendanceRecords.student', 'classroom.students']);

        return $this->success('Attendance submitted.', [
            'classroom' => $this->serializeClassroom($classroom),
            'session' => $this->serializeSession($session),
            'records' => $this->serializeStudentRecords($classroom->students, $session),
        ], 201);
    }

    public function parentHistory(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');

        $studentIds = AccessControl::legacyStudentIdsForUser($user);

        $records = AttendanceRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('attendanceSession')
            ->with('attendanceSession.classroom:id,name', 'student:id,full_name')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (AttendanceRecord $record): array => [
                'id' => $record->id,
                'student_id' => $record->student_id,
                'student_name' => $record->student?->full_name,
                'session_date' => $record->attendanceSession?->session_date?->toDateString(),
                'attendance_date' => $record->attendanceSession?->session_date?->toDateString(),
                'lesson_number' => $record->attendanceSession?->lesson_number,
                'session_name' => $record->attendanceSession?->session_name,
                'class_name' => $record->attendanceSession?->classroom?->name,
                'status' => $record->status,
                'status_label' => $this->statusLabel($record->status),
                'late_minutes' => $record->late_minutes,
                'email_status' => $record->email_status,
                'email_status_label' => $this->emailStatusLabel($record->email_status),
                'emailed_at' => $record->emailed_at?->toDateTimeString(),
                'email_sent_by' => $record->email_sent_by,
            ])
            ->values()
            ->all();

        return $this->success('Attendance records retrieved.', $records);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $query = AttendanceSession::query()
            ->with('classroom.course:id,name,grade_level,start_date,end_date')
            ->withCount([
                'attendanceRecords as present_count' => fn ($query) => $query->where('status', 'present'),
                'attendanceRecords as late_count' => fn ($query) => $query->where('status', 'late'),
                'attendanceRecords as absent_count' => fn ($query) => $query->where('status', 'absent'),
            ])
            ->orderByDesc('session_date')
            ->orderByDesc('id');

        if ($user->isTeacher() || $user->isAssistant()) {
            $query->whereIn('classroom_id', $this->assignedClassroomIds($user));
        } elseif (! $user->isAdmin()) {
            return $this->error('Forbidden.', 403);
        }

        $sessions = $query->limit(50)->get()->map(fn (AttendanceSession $session): array => [
            'id' => $session->id,
            'class_name' => $session->classroom?->name,
            'session_date' => $session->session_date?->toDateString(),
            'attendance_date' => $session->session_date?->toDateString(),
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'status' => $session->status,
            'lesson_number' => $session->lesson_number,
            'session_name' => $session->session_name,
            'present' => $session->present_count,
            'late' => $session->late_count,
            'absent' => $session->absent_count,
        ]);

        return $this->success('Attendance history retrieved.', $sessions->all());
    }

    private function serializeStudentRecords(Collection $students, ?AttendanceSession $session): array
    {
        $records = $session
            ? $session->attendanceRecords->keyBy('student_id')
            : collect();

        return $students->map(function (Student $student) use ($records): array {
            $record = $records->get($student->id);

            return [
                'id' => $record?->id,
                'student_id' => $student->id,
                'student_code' => $student->student_code,
                'student_name' => $student->full_name,
                'status' => $record?->status,
                'status_label' => $this->statusLabel($record?->status),
                'late_minutes' => $record?->late_minutes ?? 0,
                'note' => $record?->note,
                'email_status' => $record?->email_status ?? AttendanceRecord::EMAIL_NOT_SENT,
                'email_status_label' => $this->emailStatusLabel($record?->email_status),
                'emailed_at' => $record?->emailed_at?->toDateTimeString(),
                'email_sent_by' => $record?->email_sent_by,
            ];
        })->values()->all();
    }

    private function serializeSession(AttendanceSession $session): array
    {
        return [
            'id' => $session->id,
            'classroom_id' => $session->classroom_id,
            'session_date' => $session->session_date?->toDateString(),
            'attendance_date' => $session->session_date?->toDateString(),
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'status' => $session->status,
            'lesson_number' => $session->lesson_number,
            'session_name' => $session->session_name,
            'note' => $session->note,
        ];
    }

    private function serializeManagedSession(AttendanceSession $session): array
    {
        return [
            'id' => $session->id,
            'classroom_id' => $session->classroom_id,
            'class_name' => $session->classroom?->name,
            'grade_level' => $this->classroomGradeLevel($session->classroom),
            'session_date' => $session->session_date?->toDateString(),
            'attendance_date' => $session->session_date?->toDateString(),
            'start_time' => $session->start_time,
            'end_time' => $session->end_time,
            'status' => $session->status,
            'lesson_number' => $session->lesson_number,
            'session_name' => $session->session_name,
            'note' => $session->note,
            'present' => $session->present_count ?? 0,
            'late' => $session->late_count ?? 0,
            'absent' => $session->absent_count ?? 0,
        ];
    }

    private function serializeClassroom(Classroom $classroom): array
    {
        return [
            'id' => $classroom->id,
            'name' => $classroom->name,
            'grade_level' => $this->classroomGradeLevel($classroom),
            'start_date' => $classroom->start_date?->toDateString(),
            'end_date' => $classroom->end_date?->toDateString(),
            'total_lessons' => $classroom->total_lessons,
        ];
    }

    private function classroomGradeLevel(?Classroom $classroom): ?int
    {
        $classroom?->loadMissing('course');

        return $classroom?->course?->grade_level ?? $classroom?->grade_level;
    }

    private function lessonNumberIsValid(Classroom $classroom, int $lessonNumber): bool
    {
        $totalLessons = (int) ($classroom->total_lessons ?: 1);

        return $lessonNumber >= 1 && $lessonNumber <= $totalLessons;
    }

    private function sessionExists(
        int $classroomId,
        string $sessionDate,
        int $lessonNumber,
        ?int $exceptSessionId = null,
    ): bool {
        return AttendanceSession::query()
            ->where('classroom_id', $classroomId)
            ->whereDate('session_date', $sessionDate)
            ->where('lesson_number', $lessonNumber)
            ->when($exceptSessionId, fn ($query) => $query->whereKeyNot($exceptSessionId))
            ->exists();
    }

    private function sessionDate(array $validated): string
    {
        return $validated['session_date'] ?? $validated['attendance_date'];
    }

    private function emailStatusLabel(?string $status): string
    {
        return match ($status) {
            AttendanceRecord::EMAIL_SENT => 'Đã gửi',
            AttendanceRecord::EMAIL_FAILED => 'Gửi lỗi',
            default => 'Chưa gửi',
        };
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            'present' => 'Có mặt',
            'late' => 'Đi muộn',
            'absent' => 'Vắng',
            default => 'Chưa điểm danh',
        };
    }

    private function canTakeAttendance(Request $request, Classroom $classroom): bool
    {
        $user = $request->attributes->get('auth_user');

        if ($user?->isAdmin()) {
            return true;
        }

        if ($user?->isTeacher() || $user?->isAssistant()) {
            return AccessControl::canAccessAssignedClassroom($user, $classroom);
        }

        return false;
    }

    private function canManageSession(Request $request, Classroom $classroom): bool
    {
        $user = $request->attributes->get('auth_user');

        if ($user?->isAdmin()) {
            return true;
        }

        if ($user?->isTeacher()) {
            return AccessControl::canAccessAssignedClassroom($user, $classroom);
        }

        return false;
    }

    public function sendEmail(Request $request, AttendanceSession $session): JsonResponse
    {
        $session->loadMissing('classroom');

        if (! $this->canTakeAttendance($request, $session->classroom)) {
            return $this->error('Forbidden.', 403);
        }

        $validated = $request->validate([
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['integer', Rule::exists('students', 'id')],
        ]);

        $records = $session->attendanceRecords()
            ->with('student:id,full_name,classroom_id')
            ->when(isset($validated['student_ids']), fn ($q) => $q->whereIn('student_id', $validated['student_ids']))
            ->get();

        if ($records->isEmpty()) {
            return $this->error('Không có bản ghi điểm danh nào để gửi email.', 422);
        }

        $user = $request->attributes->get('auth_user');
        $sent = 0;
        $failed = 0;

        foreach ($records as $record) {
            if (! $record->student) {
                continue;
            }

            $parentEmail = $this->getParentEmail($record);
            if (! $parentEmail) {
                $failed++;
                continue;
            }

            try {
                \Illuminate\Support\Facades\Mail::send(
                    new \App\Mail\AttendanceNotification(
                        title: 'Thông báo điểm danh',
                        content: "Học sinh {$record->student->full_name} có trạng thái điểm danh: {$this->statusLabel($record->status)}.",
                        studentName: $record->student->full_name,
                        className: $session->classroom->name,
                        date: $session->session_date?->toDateString() ?? '',
                        statusLabel: $this->statusLabel($record->status),
                    ),
                );

                $record->update([
                    'email_status' => AttendanceRecord::EMAIL_SENT,
                    'emailed_at' => now(),
                    'email_sent_by' => $user->id,
                ]);

                \App\Models\EmailLog::query()->create([
                    'recipient_email' => $parentEmail,
                    'recipient_name' => $record->student->full_name,
                    'subject' => 'PTConnect - Thông báo điểm danh',
                    'content' => "Thông báo điểm danh cho học sinh {$record->student->full_name} - {$this->statusLabel($record->status)}",
                    'type' => 'attendance',
                    'status' => 'sent',
                    'sent_at' => now(),
                    'related_type' => 'attendance_record',
                    'related_id' => $record->id,
                ]);

                $sent++;
            } catch (\Throwable $e) {
                $record->update([
                    'email_status' => AttendanceRecord::EMAIL_FAILED,
                ]);

                \App\Models\EmailLog::query()->create([
                    'recipient_email' => $parentEmail,
                    'recipient_name' => $record->student->full_name,
                    'subject' => 'PTConnect - Thông báo điểm danh',
                    'content' => "Thông báo điểm danh cho học sinh {$record->student->full_name} - {$this->statusLabel($record->status)}",
                    'type' => 'attendance',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'sent_at' => null,
                    'related_type' => 'attendance_record',
                    'related_id' => $record->id,
                ]);

                $failed++;
            }
        }

        return $this->success('Email đã được gửi.', [
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }

    private function getParentEmail(AttendanceRecord $record): ?string
    {
        $student = $record->student;
        if (! $student) {
            return null;
        }

        $classroom = $student->classroom()->with('students.parents')->first();

        $profile = StudentProfile::query()
            ->where('student_code', $student->student_code)
            ->first();

        if ($profile?->parent_email) {
            return $profile->parent_email;
        }

        $parent = $student->parents()->first();
        return $parent?->email;
    }

    private function assignedClassroomIds($user): array
    {
        return AccessControl::assignedClassroomIds($user);
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
