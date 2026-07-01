<?php

namespace App\Services;

use App\Repositories\AttendanceRecordRepository;
use App\Repositories\AttendanceSessionRepository;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(
        protected AttendanceSessionRepository $sessionRepo,
        protected AttendanceRecordRepository $recordRepo,
        protected EmailNotificationService $emailService,
    ) {}

    public function submitAttendance(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $session = $this->sessionRepo->updateOrCreate(
                [
                    'classroom_id' => $data['classroom_id'],
                    'attendance_date' => $data['attendance_date'],
                    'lesson_number' => $data['lesson_number'],
                ],
                [
                    'session_name' => $data['session_name'] ?? "Lesson {$data['lesson_number']}",
                    'status' => 'completed',
                ]
            );

            $records = [];
            foreach ($data['records'] as $record) {
                $attendanceRecord = $this->recordRepo->updateOrCreate(
                    [
                        'attendance_session_id' => $session->id,
                        'student_id' => $record['student_id'],
                    ],
                    [
                        'status' => $record['status'],
                        'late_minutes' => $record['late_minutes'] ?? ($record['status'] === 'late' ? 15 : null),
                        'note' => $record['note'] ?? null,
                        'email_status' => $this->getInitialEmailStatus($record['status']),
                    ]
                );

                $records[] = [
                    'student_id' => $attendanceRecord->student_id,
                    'status' => $attendanceRecord->status,
                    'late_minutes' => $attendanceRecord->late_minutes,
                    'email_status' => $attendanceRecord->email_status,
                ];
            }

            return [
                'session' => $session,
                'records' => $records,
            ];
        });
    }

    public function closeSession(int $sessionId): array
    {
        $session = $this->sessionRepo->findOrFail($sessionId);

        $this->sessionRepo->update($session, ['status' => 'completed']);

        $absentLateRecords = $this->recordRepo->findAbsentLatePending($sessionId);

        foreach ($absentLateRecords as $record) {
            $this->recordRepo->update($record, ['email_status' => 'pending']);
        }

        $records = $this->recordRepo->newQuery()
            ->where('attendance_session_id', $sessionId)
            ->get();

        return [
            'session' => $session,
            'records' => $records,
        ];
    }

    private function getInitialEmailStatus(string $attendanceStatus): string
    {
        return in_array($attendanceStatus, ['absent', 'late']) ? 'pending' : 'not_required';
    }

    public function autoSendEmails(): void
    {
        $pendingRecords = $this->recordRepo->findPendingEmailRecords();

        foreach ($pendingRecords as $record) {
            $this->emailService->sendAttendanceEmail($record);
        }
    }
}
