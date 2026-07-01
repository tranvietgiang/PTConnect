<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\SubmitAttendanceRequest;
use App\Repositories\AttendanceRecordRepository;
use App\Repositories\AttendanceSessionRepository;
use App\Repositories\ClassroomRepository;
use App\Repositories\StudentProfileRepository;
use App\Repositories\UserRepository;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected AttendanceSessionRepository $sessionRepo,
        protected AttendanceRecordRepository $recordRepo,
        protected StudentProfileRepository $studentRepo,
        protected ClassroomRepository $classroomRepo,
        protected UserRepository $userRepo,
    ) {}

    public function today(Request $request): JsonResponse
    {
        $classroomId = $request->input('classroom_id');
        $date = $request->input('date', now()->toDateString());
        $lessonNumber = $request->input('lesson_number', 1);

        $session = $this->sessionRepo->findCurrent($classroomId, $date, $lessonNumber);

        $students = $this->studentRepo->getActiveStudentsByClassroom($classroomId);

        $records = collect();
        if ($session) {
            $records = $this->recordRepo->newQuery()
                ->where('attendance_session_id', $session->id)
                ->get()
                ->keyBy('student_id');
        }

        $records = $students->map(function ($student) use ($records, $session) {
            $existing = $records->get($student->id);

            return [
                'student_id' => $student->id,
                'student_code' => $student->student_code,
                'student_name' => $student->full_name,
                'status' => $existing?->status ?? 'present',
                'late_minutes' => $existing?->late_minutes,
                'email_status' => $existing?->email_status ?? 'not_required',
                'note' => $existing?->note,
            ];
        });

        return response()->json([
            'session' => [
                'id' => $session?->id,
                'session_name' => $session?->session_name ?? "Lesson {$lessonNumber}",
                'status' => $session?->status ?? 'scheduled',
            ],
            'records' => $records,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $query = $this->sessionRepo->newQuery()
            ->orderBy('attendance_date', 'desc')
            ->orderBy('lesson_number', 'desc');

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        if ($request->filled('from_date')) {
            $query->where('attendance_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->where('attendance_date', '<=', $request->input('to_date'));
        }

        $sessions = $query->paginate($request->input('per_page', 20));

        $sessions->getCollection()->transform(function ($session) {
            $classroom = $this->classroomRepo->find($session->classroom_id);
            $records = $this->recordRepo->newQuery()
                ->where('attendance_session_id', $session->id)
                ->get();

            return [
                'id' => $session->id,
                'attendance_date' => $session->attendance_date,
                'lesson_number' => $session->lesson_number,
                'session_name' => $session->session_name,
                'status' => $session->status,
                'classroom' => $classroom,
                'records' => $records,
            ];
        });

        return response()->json($sessions);
    }

    public function submit(SubmitAttendanceRequest $request): JsonResponse
    {
        $result = $this->attendanceService->submitAttendance($request->validated());

        return response()->json([
            'message' => 'Đã gửi điểm danh thành công.',
            'records' => $result['records'],
        ]);
    }

    public function studentHistory(Request $request): JsonResponse
    {
        $studentId = $request->input('student_id');

        $records = $this->recordRepo->newQuery()
            ->where('student_id', $studentId)
            ->get();

        $sessionIds = $records->pluck('attendance_session_id')->unique();
        $sessions = $this->sessionRepo->newQuery()
            ->whereIn('id', $sessionIds)
            ->where('status', 'completed')
            ->get()
            ->keyBy('id');

        $classroomIds = $sessions->pluck('classroom_id')->unique();
        $classrooms = $this->classroomRepo->newQuery()
            ->whereIn('id', $classroomIds)
            ->get()
            ->keyBy('id');

        $result = $records->map(function ($record) use ($sessions, $classrooms) {
            $session = $sessions->get($record->attendance_session_id);
            $classroom = $session ? $classrooms->get($session->classroom_id) : null;

            return [
                'id' => $record->id,
                'student_id' => $record->student_id,
                'status' => $record->status,
                'late_minutes' => $record->late_minutes,
                'attendance_date' => $session?->attendance_date,
                'lesson_number' => $session?->lesson_number,
                'classroom_name' => $classroom?->name,
            ];
        })->filter(fn($r) => $r['attendance_date'] !== null)->values();

        return response()->json([
            'data' => $result,
        ]);
    }

    public function sessions(Request $request): JsonResponse
    {
        $query = $this->sessionRepo->newQuery()
            ->orderBy('attendance_date', 'desc');

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->input('classroom_id'));
        }

        $sessions = $query->paginate($request->input('per_page', 20));

        $sessions->getCollection()->transform(function ($session) {
            $classroom = $this->classroomRepo->find($session->classroom_id);
            $records = $this->recordRepo->newQuery()
                ->where('attendance_session_id', $session->id)
                ->get();

            return [
                'id' => $session->id,
                'attendance_date' => $session->attendance_date,
                'lesson_number' => $session->lesson_number,
                'session_name' => $session->session_name,
                'status' => $session->status,
                'classroom' => $classroom,
                'records' => $records,
            ];
        });

        return response()->json($sessions);
    }

    public function storeSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'attendance_date' => 'required|date',
            'lesson_number' => 'required|integer|min:1',
            'session_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'note' => 'nullable|string|max:1000',
        ]);

        $session = $this->sessionRepo->create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Đã tạo buổi điểm danh.',
            'data' => $session,
        ], 201);
    }

    public function storeSessionsBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'sessions' => 'required|array|min:1',
            'sessions.*.attendance_date' => 'required|date',
            'sessions.*.lesson_number' => 'required|integer|min:1',
            'sessions.*.session_name' => 'nullable|string|max:255',
        ]);

        $createdBy = $request->user()->id;
        $sessions = collect($validated['sessions'])->map(function ($session) use ($validated, $createdBy) {
            return $this->sessionRepo->create([
                'classroom_id' => $validated['classroom_id'],
                'attendance_date' => $session['attendance_date'],
                'lesson_number' => $session['lesson_number'],
                'session_name' => $session['session_name'] ?? "Lesson {$session['lesson_number']}",
                'created_by' => $createdBy,
            ]);
        });

        return response()->json([
            'message' => 'Đã tạo các buổi điểm danh.',
            'data' => $sessions,
        ], 201);
    }

    public function showSession(int $id): JsonResponse
    {
        $session = $this->sessionRepo->findOrFail($id);
        $classroom = $this->classroomRepo->find($session->classroom_id);
        $creator = $this->userRepo->find($session->created_by);
        $records = $this->recordRepo->newQuery()
            ->where('attendance_session_id', $id)
            ->get();

        return response()->json([
            'data' => [
                'session' => $session,
                'classroom' => $classroom,
                'creator' => $creator,
                'records' => $records,
            ],
        ]);
    }

    public function updateSession(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'note' => 'nullable|string|max:1000',
        ]);

        $session = $this->sessionRepo->findOrFail($id);
        $this->sessionRepo->update($session, $validated);

        return response()->json([
            'message' => 'Đã cập nhật buổi điểm danh.',
            'data' => $session,
        ]);
    }

    public function destroySession(int $id): JsonResponse
    {
        $this->sessionRepo->deleteById($id);

        return response()->json([
            'message' => 'Đã xóa buổi điểm danh.',
        ]);
    }

    public function closeSession(int $id): JsonResponse
    {
        $result = $this->attendanceService->closeSession($id);

        return response()->json([
            'message' => 'Đã kết thúc buổi điểm danh.',
            'data' => $result,
        ]);
    }
}
