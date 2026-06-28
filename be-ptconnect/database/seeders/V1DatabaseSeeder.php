<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class V1DatabaseSeeder extends Seeder
{
    private const MIN_STUDENTS_PER_CLASS = 40;
    private const MAX_STUDENTS_PER_CLASS = 60;

    public function run(): void
    {
        $admin = $this->user('admin@ptconnect.test', 'admin', 'admin', '12345678');
        $teacher = $this->user('teacher@ptconnect.test', 'teacher', 'teacher', '12345678');
        $assistant = $this->user('assistant@ptconnect.test', 'assistant', 'assistant', '12345678');

        $academicYear = AcademicYear::query()->updateOrCreate(
            ['name' => '2025-2026'],
            [
                'start_date' => '2025-08-01',
                'end_date' => '2026-05-31',
                'is_active' => true,
            ],
        );

        $classrooms = $this->seedClassrooms($academicYear, $teacher, $assistant);
        $this->seedStudentsAndParents($classrooms);
        $this->seedAssignments($teacher, $classrooms);
        $this->seedAttendanceAndNotificationDemo($assistant, $classrooms['10A1']);
    }

    private function user(string $email, string $username, string $role, string $password): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'username' => $username,
                'password' => Hash::make($password),
                'role' => $role,
                'is_active' => true,
            ],
        );
    }

    private function seedClassrooms(AcademicYear $academicYear, User $teacher, User $assistant)
    {
        return collect([10, 11, 12])
            ->flatMap(fn(int $grade): array => collect(range(1, 4))
                ->map(fn(int $index): array => [
                    'name' => "{$grade}A{$index}",
                    'grade_level' => $grade,
                ])
                ->all())
            ->mapWithKeys(function (array $classroom) use ($academicYear, $teacher, $assistant): array {
                $model = Classroom::query()->updateOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'name' => $classroom['name'],
                    ],
                    [
                        'grade_level' => $classroom['grade_level'],
                        'description' => 'Lớp dạy thêm môn Sinh học, sĩ số khoảng 60-70 học sinh.',
                        'is_active' => true,
                    ],
                );

                $model->users()->syncWithoutDetaching([
                    $teacher->id => ['role_in_class' => 'teacher'],
                    $assistant->id => ['role_in_class' => 'assistant'],
                ]);

                return [$classroom['name'] => $model];
            });
    }

    private function seedStudentsAndParents($classrooms): void
    {
        $firstNames = [
            'An',
            'Bao',
            'Chi',
            'Dung',
            'Giang',
            'Hanh',
            'Khanh',
            'Lan',
            'Linh',
            'Minh',
            'Nam',
            'Nhi',
            'Phuc',
            'Quan',
            'Thao',
            'Trang',
            'Tuan',
            'Vy',
            'Yen',
            'Khoa',
        ];
        $lastNames = ['Nguyen', 'Tran', 'Le', 'Pham', 'Hoang', 'Huynh', 'Phan', 'Vu', 'Dang', 'Bui'];

        foreach ($classrooms as $classroomName => $classroom) {
            $grade = (int) $classroom->grade_level;
            $classIndex = (int) substr($classroomName, -1);
            $studentsPerClass = random_int(self::MIN_STUDENTS_PER_CLASS, self::MAX_STUDENTS_PER_CLASS);

            for ($number = 1; $number <= $studentsPerClass; $number++) {
                $studentCode = sprintf('HS%02d%02d%03d', $grade, $classIndex, $number);
                $fullName = $lastNames[($number + $classIndex) % count($lastNames)] . ' '
                    . $firstNames[($number - 1) % count($firstNames)] . ' '
                    . sprintf('%02d', $number);

                $student = Student::query()->updateOrCreate(
                    ['student_code' => $studentCode],
                    [
                        'classroom_id' => $classroom->id,
                        'full_name' => $fullName,
                        'gender' => $number % 2 === 0 ? 'female' : 'male',
                        'date_of_birth' => Carbon::create(2026 - $grade - 6, (($number - 1) % 12) + 1, (($number - 1) % 27) + 1)->toDateString(),
                        'phone' => sprintf('09%08d', ($grade * 100000) + ($classIndex * 1000) + $number),
                        'address' => "Khu vực lớp {$classroomName}",
                        'status' => 'studying',
                    ],
                );

                $parentUser = User::query()->updateOrCreate(
                    ['username' => $studentCode],
                    [
                        'email' => strtolower($studentCode) . '@parent.ptconnect.test',
                        'password' => Hash::make('12345678'),
                        'role' => 'parent',
                        'is_active' => true,
                    ],
                );

                ParentProfile::query()->updateOrCreate(
                    ['user_id' => $parentUser->id],
                    [
                        'student_id' => $student->id,
                        'full_name' => 'Phụ huynh ' . $fullName,
                        'email' => strtolower($studentCode) . '@parent.ptconnect.test',
                        'phone' => sprintf('08%08d', ($grade * 100000) + ($classIndex * 1000) + $number),
                        'relationship' => $number % 2 === 0 ? 'mother' : 'father',
                        'address' => "Khu vực lớp {$classroomName}",
                    ],
                );
            }
        }
    }

    private function seedAssignments(User $teacher, $classrooms): void
    {
        $topics = [
            ['title' => 'Ôn tập phần di truyền cơ bản', 'description' => 'Hoàn thành bài ôn tập và nộp file trước hạn.'],
            ['title' => 'Bài tập về cấu trúc AND và ARN', 'description' => 'Giải các bài tập liên quan đến cấu trúc AND, ARN và nguyên tắc bổ sung.'],
            ['title' => 'Bài tập lai một tính trạng', 'description' => 'Vận dụng quy luật phân li của Mendel để giải bài tập lai.'],
            ['title' => 'Bài tập về đột biến gen', 'description' => 'Phân tích các dạng đột biến gen và hậu quả.'],
        ];

        foreach ($classrooms as $classroomName => $classroom) {
            $topic = $topics[array_rand($topics)];
            $sanitized = strtolower(str_replace([' ', ',', '.'], '-', $topic['title']));
            $fileName = "{$sanitized}-{$classroomName}.txt";
            $filePath = "assignments/attachments/{$fileName}";

            Storage::disk('local')->put($filePath, "PTConnect\n{$topic['title']} - {$classroomName}\n");

            $assignment = Assignment::query()->updateOrCreate(
                [
                    'created_by' => $teacher->id,
                    'title' => "{$topic['title']} cho {$classroomName}",
                ],
                [
                    'description' => $topic['description'],
                    'classroom_id' => $classroom->id,
                    'grade_level' => null,
                    'due_date' => Carbon::now()->addDays(random_int(3, 7))->toDateString(),
                    'attachment_path' => $filePath,
                    'attachment_name' => $fileName,
                    'attachment_mime' => 'text/plain',
                    'status' => 'published',
                ],
            );

            $students = $classroom->students()->inRandomOrder()->take(random_int(3, 5))->get();

            foreach ($students as $student) {
                $submissionPath = "assignments/submissions/{$student->student_code}-{$assignment->id}.txt";
                Storage::disk('local')->put($submissionPath, "Bai nop cua {$student->student_code}\n");

                AssignmentSubmission::query()->updateOrCreate(
                    [
                        'assignment_id' => $assignment->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'submitted_file_path' => $submissionPath,
                        'submitted_file_name' => "{$student->student_code}-{$assignment->id}.txt",
                        'submitted_file_mime' => 'text/plain',
                        'submitted_at' => now()->subHours(random_int(1, 48)),
                        'status' => 'submitted',
                        'score' => round(random_int(50, 100) / 10, 1),
                        'teacher_comment' => 'Giáo viên đã chấm bài.',
                    ],
                );
            }
        }
    }

    private function seedAttendanceAndNotificationDemo(User $assistant, Classroom $classroom): void
    {
        $session = AttendanceSession::query()->updateOrCreate(
            [
                'classroom_id' => $classroom->id,
                'attendance_date' => now()->toDateString(),
                'session_name' => 'Buổi học Sinh học',
            ],
            [
                'created_by' => $assistant->id,
                'note' => 'Trợ giảng điểm danh demo.',
            ],
        );

        $students = $classroom->students()->orderBy('student_code')->limit(3)->get();

        foreach ($students as $index => $student) {
            AttendanceRecord::query()->updateOrCreate(
                [
                    'attendance_session_id' => $session->id,
                    'student_id' => $student->id,
                ],
                [
                    'status' => $index === 0 ? 'present' : ($index === 1 ? 'late' : 'absent'),
                    'late_minutes' => $index === 1 ? 15 : 0,
                    'note' => $index === 0 ? null : 'Thông báo demo gửi phụ huynh.',
                    'email_sent_at' => $index === 0 ? null : now(),
                ],
            );
        }

        $notification = Notification::query()->updateOrCreate(
            [
                'title' => 'Thông báo điểm danh lớp 10A1',
                'sender_id' => $assistant->id,
                'classroom_id' => $classroom->id,
                'type' => 'attendance',
            ],
            [
                'content' => 'Hệ thống gửi thông báo cho phụ huynh khi học sinh đi trễ hoặc vắng.',
                'target_type' => 'class',
                'grade_level' => null,
                'student_id' => null,
                'parent_id' => null,
            ],
        );

        $students->skip(1)->each(function (Student $student) use ($notification): void {
            $parent = $student->parents()->first();

            if (! $parent) {
                return;
            }

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
        });
    }
}
