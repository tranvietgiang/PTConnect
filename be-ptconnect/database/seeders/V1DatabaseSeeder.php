<?php

namespace Database\Seeders;

use App\Models\AssistantAssignment;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\StudentEnrollment;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class V1DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $systemAdmin = $this->user(
            email: 'system.admin@ptconnect.test',
            name: 'System Admin',
            username: 'system_admin',
            role: User::ROLE_SYSTEM_ADMIN,
        );

        $schoolAdmin = $this->user(
            email: 'school.admin@ptconnect.test',
            name: 'School Admin',
            username: 'school_admin',
            role: User::ROLE_SCHOOL_ADMIN,
        );

        $teacher = $this->user(
            email: 'teacher@ptconnect.test',
            name: 'Teacher Demo',
            username: 'teacher',
            role: User::ROLE_TEACHER,
        );

        $assistant = $this->user(
            email: 'assistant@ptconnect.test',
            name: 'Assistant Demo',
            username: 'assistant',
            role: User::ROLE_ASSISTANT,
        );

        $course = Course::query()->updateOrCreate(
            ['name' => 'Sinh hoc 10 - 2026'],
            [
                'grade_level' => 10,
                'start_date' => '2026-06-01',
                'end_date' => '2026-12-31',
                'status' => Course::STATUS_ACTIVE,
            ],
        );

        $classroom = Classroom::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'name' => '10A1',
            ],
            [
                'teacher_id' => $teacher->id,
                'status' => 'active',
            ],
        );

        $students = [
            [
                'student_code' => 'PTC100001',
                'full_name' => 'Nguyen Minh An',
                'student_email' => 'student.an@ptconnect.test',
                'parent_email' => 'parent.an@example.com',
                'parent_full_name' => 'Nguyen Van Binh',
                'parent_relation' => 'father',
            ],
            [
                'student_code' => 'PTC100002',
                'full_name' => 'Tran Bao Chau',
                'student_email' => 'student.chau@ptconnect.test',
                'parent_email' => 'parent.chau@example.com',
                'parent_full_name' => 'Tran Thi Hoa',
                'parent_relation' => 'mother',
            ],
            [
                'student_code' => 'PTC100003',
                'full_name' => 'Le Gia Huy',
                'student_email' => 'student.huy@ptconnect.test',
                'parent_email' => 'parent.huy@example.com',
                'parent_full_name' => 'Le Van Hai',
                'parent_relation' => 'father',
            ],
        ];

        foreach ($students as $index => $studentData) {
            $studentUser = $this->user(
                email: $studentData['student_email'],
                name: $studentData['full_name'],
                username: $studentData['student_code'],
                role: User::ROLE_STUDENT,
            );

            $profile = StudentProfile::query()->updateOrCreate(
                ['student_code' => $studentData['student_code']],
                [
                    'user_id' => $studentUser->id,
                    'full_name' => $studentData['full_name'],
                    'student_email' => $studentData['student_email'],
                    'parent_email' => $studentData['parent_email'],
                    'high_school_name' => 'THPT Nguyen Trai',
                    'student_phone' => sprintf('09010000%02d', $index + 1),
                    'parent_phone' => sprintf('09110000%02d', $index + 1),
                    'parent_full_name' => $studentData['parent_full_name'],
                    'parent_relation' => $studentData['parent_relation'],
                ],
            );

            StudentEnrollment::query()->updateOrCreate(
                [
                    'student_id' => $profile->id,
                    'course_id' => $course->id,
                    'classroom_id' => $classroom->id,
                ],
                [
                    'status' => StudentEnrollment::STATUS_ACTIVE,
                    'enrolled_at' => now(),
                    'ended_at' => null,
                ],
            );
        }

        AssistantAssignment::query()->updateOrCreate(
            [
                'assistant_id' => $assistant->id,
                'course_id' => $course->id,
                'classroom_id' => $classroom->id,
            ],
            [
                'status' => AssistantAssignment::STATUS_ACTIVE,
                'assigned_at' => now(),
                'ended_at' => null,
                'locked_at' => null,
            ],
        );

        unset($systemAdmin, $schoolAdmin);
    }

    private function user(string $email, string $name, string $username, string $role): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'password' => Hash::make('12345678'),
                'role' => $role,
                'is_active' => true,
            ],
        );
    }
}
