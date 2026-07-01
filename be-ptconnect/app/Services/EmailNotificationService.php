<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\ScoreRecord;
use App\Models\StudentProfile;
use App\Repositories\AttendanceRecordRepository;
use App\Repositories\AttendanceSessionRepository;
use App\Repositories\ClassroomRepository;
use App\Repositories\CourseRepository;
use App\Repositories\EmailNotificationRepository;
use App\Repositories\ScoreColumnRepository;
use App\Repositories\ScoreRecordRepository;
use App\Repositories\StudentProfileRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    public function __construct(
        protected EmailNotificationRepository $emailNotificationRepo,
        protected StudentProfileRepository $studentRepo,
        protected AttendanceRecordRepository $attendanceRecordRepo,
        protected ScoreRecordRepository $scoreRecordRepo,
        protected AttendanceSessionRepository $sessionRepo,
        protected ClassroomRepository $classroomRepo,
        protected CourseRepository $courseRepo,
        protected ScoreColumnRepository $scoreColumnRepo,
    ) {}

    public function sendAttendanceEmail(AttendanceRecord $record): bool
    {
        $student = $this->studentRepo->find($record->student_id);

        if (!$student || empty($student->parent_email)) {
            $this->attendanceRecordRepo->update($record, [
                'email_status' => 'failed',
                'email_error' => 'Không có email phụ huynh',
            ]);
            return false;
        }

        $session = $this->sessionRepo->find($record->attendance_session_id);
        $classroomName = $session ? $this->getClassName($session->classroom_id) : '';

        $statusLabel = $record->status === 'absent' ? 'Vắng' : 'Đi muộn';
        $minutesText = $record->status === 'late' ? " ({$record->late_minutes} phút)" : '';

        $subject = "[PTConnect] Thông báo điểm danh - {$student->full_name}";
        $content = "Xin chào,\n\n"
            . "Học sinh {$student->full_name} ({$student->student_code})\n"
            . "Lớp: {$classroomName}\n"
            . "Ngày: {$session->attendance_date} (Buổi {$session->lesson_number})\n"
            . "Trạng thái: {$statusLabel}{$minutesText}\n\n"
            . "Vui lòng liên hệ giáo viên chủ nhiệm để biết thêm chi tiết.\n\n"
            . "Trân trọng,\nPTConnect";

        return $this->send(
            student: $student,
            recipientEmail: $student->parent_email,
            recipientName: $student->parent_name,
            subject: $subject,
            content: $content,
            type: 'attendance',
            referenceType: 'attendance_records',
            referenceId: $record->id,
            record: $record,
        );
    }

    public function sendScoreEmail(ScoreRecord $record): bool
    {
        $student = $this->studentRepo->find($record->student_id);

        if (!$student || empty($student->parent_email)) {
            $this->scoreRecordRepo->update($record, [
                'email_status' => 'failed',
                'email_error' => 'Không có email phụ huynh',
            ]);
            return false;
        }

        $column = $this->scoreColumnRepo->find($record->score_column_id);
        $courseName = $column ? $this->getCourseNameByClassroom($column->classroom_id) : '';

        $subject = "[PTConnect] Thông báo điểm - {$student->full_name}";
        $content = "Xin chào,\n\n"
            . "Học sinh {$student->full_name} ({$student->student_code})\n"
            . "Môn: {$courseName}\n"
            . "Bài kiểm tra: {$column->name}\n"
            . "Điểm: {$record->score}" . ($column->max_score ? "/{$column->max_score}" : '') . "\n\n"
            . "Trân trọng,\nPTConnect";

        return $this->send(
            student: $student,
            recipientEmail: $student->parent_email,
            recipientName: $student->parent_name,
            subject: $subject,
            content: $content,
            type: 'score',
            referenceType: 'score_records',
            referenceId: $record->id,
            record: $record,
        );
    }

    public function sendCustomEmail(StudentProfile $student, string $subject, string $content, int $userId): bool
    {
        return $this->send(
            student: $student,
            recipientEmail: $student->parent_email,
            recipientName: $student->parent_name,
            subject: $subject,
            content: $content,
            type: 'general',
            createdBy: $userId,
        );
    }

    private function getClassName(int $classroomId): string
    {
        $classroom = $this->classroomRepo->find($classroomId);
        return $classroom?->name ?? '';
    }

    private function getCourseNameByClassroom(int $classroomId): string
    {
        $classroom = $this->classroomRepo->find($classroomId);
        if (!$classroom || !$classroom->course_id) return '';

        $course = $this->courseRepo->find($classroom->course_id);
        return $course?->name ?? '';
    }

    private function send(
        StudentProfile $student,
        string $recipientEmail,
        ?string $recipientName,
        string $subject,
        string $content,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        $record = null,
        ?int $createdBy = null,
    ): bool {
        try {
            Mail::raw($content, function ($message) use ($recipientEmail, $recipientName, $subject) {
                $message->to($recipientEmail, $recipientName)
                    ->subject($subject);
            });

            if ($record) {
                $this->attendanceRecordRepo->update($record, [
                    'email_status' => 'sent',
                    'email_sent_at' => now(),
                    'email_error' => null,
                ]);
            }

            $this->emailNotificationRepo->create([
                'student_id' => $student->id,
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'content' => $content,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => 'sent',
                'sent_at' => now(),
                'created_by' => $createdBy,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Email send failed: {$e->getMessage()}", [
                'recipient' => $recipientEmail,
                'type' => $type,
            ]);

            if ($record) {
                $this->attendanceRecordRepo->update($record, [
                    'email_status' => 'failed',
                    'email_error' => $e->getMessage(),
                ]);
            }

            $this->emailNotificationRepo->create([
                'student_id' => $student->id,
                'recipient_email' => $recipientEmail,
                'recipient_name' => $recipientName,
                'subject' => $subject,
                'content' => $content,
                'type' => $type,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_by' => $createdBy,
            ]);

            return false;
        }
    }

    public function sendEmails(array $data, int $userId): array
    {
        $results = ['sent' => 0, 'failed' => 0];

        $students = $this->studentRepo->newQuery()
            ->whereIn('id', $data['student_ids'])
            ->get();

        foreach ($students as $student) {
            if ($data['type'] === 'attendance') {
                $records = $this->attendanceRecordRepo->newQuery()
                    ->where('student_id', $student->id)
                    ->where('email_status', 'pending')
                    ->get();

                foreach ($records as $record) {
                    $success = $this->sendAttendanceEmail($record);
                    $success ? $results['sent']++ : $results['failed']++;
                }
            } elseif ($data['type'] === 'score') {
                $records = $this->scoreRecordRepo->findPendingByStudent(
                    $student->id,
                    $data['reference_id'] ?? null
                );

                foreach ($records as $record) {
                    $success = $this->sendScoreEmail($record);
                    $success ? $results['sent']++ : $results['failed']++;
                }
            } elseif ($data['type'] === 'general') {
                $success = $this->sendCustomEmail(
                    $student,
                    $data['subject'],
                    $data['content'],
                    $userId
                );
                $success ? $results['sent']++ : $results['failed']++;
            }
        }

        return $results;
    }
}
