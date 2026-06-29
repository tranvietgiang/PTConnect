<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\AttendanceNotification;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\EmailLog;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\ParentProfile;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
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
            ->whereDate('attendance_date', $date)
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
            'attendance_date' => ['required', 'date'],
            'lesson_number' => ['nullable', 'integer', 'min:1'],
            'session_name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'records.*.status' => ['required', Rule::in(['present', 'late', 'absent'])],
            'records.*.late_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'records.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $classroom = Classroom::query()->with('students.parents')->findOrFail($validated['classroom_id']);

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

        $session = AttendanceSession::query()->updateOrCreate(
            [
                'classroom_id' => $classroom->id,
                'attendance_date' => $validated['attendance_date'],
                'lesson_number' => $lessonNumber,
            ],
            [
                'session_name' => $validated['session_name'] ?? "Lesson {$lessonNumber}",
                'created_by' => $request->attributes->get('auth_user')->id,
                'note' => $validated['note'] ?? null,
            ],
        );

        $students = $classroom->students->keyBy('id');

        foreach ($validated['records'] as $record) {
            $status = $record['status'];
            $lateMinutes = $status === 'late' ? (int) ($record['late_minutes'] ?? 0) : 0;

            $attendanceRecord = AttendanceRecord::query()->updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'student_id' => $record['student_id'],
                ],
                [
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                    'note' => $record['note'] ?? null,
                    'email_sent_at' => in_array($status, ['late', 'absent'], true) ? now() : null,
                ],
            );

            if (in_array($status, ['late', 'absent'], true)) {
                $student = $students->get((int) $record['student_id']);

                if ($student) {
                    $this->createParentNotification($session, $attendanceRecord, $student, $classroom);
                }
            }
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

        $studentIds = ParentProfile::query()
            ->where('user_id', $user->id)
            ->pluck('student_id')
            ->unique()
            ->values()
            ->map(fn ($id): int => (int) $id)
            ->all();

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
                'attendance_date' => $record->attendanceSession?->attendance_date?->toDateString(),
                'lesson_number' => $record->attendanceSession?->lesson_number,
                'session_name' => $record->attendanceSession?->session_name,
                'class_name' => $record->attendanceSession?->classroom?->name,
                'status' => $record->status,
                'status_label' => $this->statusLabel($record->status),
                'late_minutes' => $record->late_minutes,
            ])
            ->values()
            ->all();

        return $this->success('Attendance records retrieved.', $records);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->attributes->get('auth_user');
        $query = AttendanceSession::query()
            ->with('classroom:id,name,grade_level')
            ->withCount([
                'attendanceRecords as present_count' => fn ($query) => $query->where('status', 'present'),
                'attendanceRecords as late_count' => fn ($query) => $query->where('status', 'late'),
                'attendanceRecords as absent_count' => fn ($query) => $query->where('status', 'absent'),
            ])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id');

        if ($user->role === 'assistant') {
            $query->whereIn('classroom_id', $this->assignedClassroomIds($user->id));
        } elseif ($user->role !== 'admin') {
            return $this->error('Forbidden.', 403);
        }

        $sessions = $query->limit(50)->get()->map(fn (AttendanceSession $session): array => [
            'id' => $session->id,
            'class_name' => $session->classroom?->name,
            'attendance_date' => $session->attendance_date?->toDateString(),
            'lesson_number' => $session->lesson_number,
            'session_name' => $session->session_name,
            'present' => $session->present_count,
            'late' => $session->late_count,
            'absent' => $session->absent_count,
        ]);

        return $this->success('Attendance history retrieved.', $sessions->all());
    }

    private function createParentNotification(
        AttendanceSession $session,
        AttendanceRecord $record,
        Student $student,
        Classroom $classroom,
    ): void {
        $statusLabel = $record->status === 'late' ? 'đi muộn' : 'vắng';
        $lateText = $record->status === 'late' && $record->late_minutes
            ? " ({$record->late_minutes} phút)"
            : '';

        $notification = Notification::query()->updateOrCreate(
            [
                'type' => 'attendance',
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
                'sender_id' => $session->created_by,
                'title' => "Điểm danh: {$student->full_name} {$statusLabel}",
            ],
            [
                'content' => "Học sinh {$student->full_name} lớp {$classroom->name} {$statusLabel}{$lateText} trong buổi học ngày {$session->attendance_date->toDateString()}.",
                'target_type' => 'student',
                'grade_level' => $classroom->grade_level,
                'parent_id' => null,
            ],
        );

        $student->loadMissing('parents');

        foreach ($student->parents as $parent) {
            NotificationRecipient::query()->updateOrCreate(
                [
                    'notification_id' => $notification->id,
                    'parent_id' => $parent->id,
                    'student_id' => $student->id,
                ],
                [
                    'email' => $parent->email,
                    'sent_at' => now(),
                    'status' => 'sent',
                ],
            );

            EmailLog::query()->updateOrCreate(
                [
                    'recipient_email' => $parent->email,
                    'type' => 'attendance',
                    'related_type' => AttendanceRecord::class,
                    'related_id' => $record->id,
                ],
                [
                    'recipient_name' => $parent->full_name,
                    'subject' => "Thông báo điểm danh {$student->full_name}",
                    'content' => $notification->content,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'error_message' => null,
                ],
            );

            try {
                Mail::to($parent->email, $parent->full_name)
                    ->queue(new AttendanceNotification(
                        title: $notification->title,
                        content: $notification->content,
                        studentName: $student->full_name,
                        className: $classroom->name,
                        date: $session->attendance_date->toDateString(),
                        statusLabel: $this->statusLabel($record->status),
                    ));
            } catch (\Throwable $e) {
                EmailLog::query()
                    ->where('recipient_email', $parent->email)
                    ->where('related_id', $record->id)
                    ->update(['error_message' => $e->getMessage()]);
            }
        }
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
            ];
        })->values()->all();
    }

    private function serializeSession(AttendanceSession $session): array
    {
        return [
            'id' => $session->id,
            'classroom_id' => $session->classroom_id,
            'attendance_date' => $session->attendance_date?->toDateString(),
            'lesson_number' => $session->lesson_number,
            'session_name' => $session->session_name,
            'note' => $session->note,
        ];
    }

    private function serializeClassroom(Classroom $classroom): array
    {
        return [
            'id' => $classroom->id,
            'name' => $classroom->name,
            'grade_level' => $classroom->grade_level,
            'start_date' => $classroom->start_date?->toDateString(),
            'end_date' => $classroom->end_date?->toDateString(),
            'total_lessons' => $classroom->total_lessons,
        ];
    }

    private function lessonNumberIsValid(Classroom $classroom, int $lessonNumber): bool
    {
        $totalLessons = (int) ($classroom->total_lessons ?: 1);

        return $lessonNumber >= 1 && $lessonNumber <= $totalLessons;
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

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'assistant') {
            return $classroom->users()
                ->where('users.id', $user->id)
                ->where('class_user_assignments.role_in_class', 'assistant')
                ->exists();
        }

        return false;
    }

    private function assignedClassroomIds(int $userId): array
    {
        return Classroom::query()
            ->whereHas('users', fn ($query) => $query
                ->where('users.id', $userId)
                ->where('class_user_assignments.role_in_class', 'assistant'))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
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
