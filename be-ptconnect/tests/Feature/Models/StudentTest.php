<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class StudentTest extends TestCase
{
    private Classroom $classroom;

    protected function setUp(): void
    {
        parent::setUp();

        $academicYear = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-05-31',
            'is_active' => true,
        ]);

        $this->classroom = Classroom::create([
            'academic_year_id' => $academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'is_active' => true,
        ]);
    }

    public function test_student_can_be_created(): void
    {
        $student = Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'STU001',
            'full_name' => 'Nguyen Van A',
            'date_of_birth' => '2010-05-15',
            'status' => 'studying',
        ]);

        $this->assertDatabaseHas('students', [
            'student_code' => 'STU001',
            'full_name' => 'Nguyen Van A',
        ]);
    }

    public function test_student_has_fillable_attributes(): void
    {
        $student = new Student();

        $this->assertEquals([
            'classroom_id',
            'student_code',
            'full_name',
            'gender',
            'date_of_birth',
            'phone',
            'address',
            'status',
        ], $student->getFillable());
    }

    public function test_student_belongs_to_classroom(): void
    {
        $student = Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'STU002',
            'full_name' => 'Nguyen Van B',
            'status' => 'studying',
        ]);

        $this->assertTrue($student->classroom()->exists());
        $this->assertSame($this->classroom->id, $student->classroom->id);
    }

    public function test_student_belongs_to_many_parents(): void
    {
        $student = Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'STU003',
            'full_name' => 'Nguyen Van C',
            'status' => 'studying',
        ]);

        $parentUser = User::factory()->create(['role' => 'parent']);

        $parent = ParentProfile::create([
            'user_id' => $parentUser->id,
            'full_name' => 'Parent C',
            'email' => 'parentc@example.com',
            'phone' => '0123456789',
            'relationship' => 'mother',
        ]);

        $student->parents()->attach($parent, ['is_primary' => true]);

        $this->assertCount(1, $student->parents);
    }

    public function test_student_code_must_be_unique(): void
    {
        Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'UNIQUE001',
            'full_name' => 'Student 1',
            'status' => 'studying',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'UNIQUE001',
            'full_name' => 'Student 2',
            'status' => 'studying',
        ]);
    }

    public function test_student_has_many_attendance_records(): void
    {
        $student = \App\Models\Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'STU010',
            'full_name' => 'Attendance Student',
            'status' => 'studying',
        ]);

        $session = \App\Models\AttendanceSession::create([
            'classroom_id' => $this->classroom->id,
            'attendance_date' => '2025-10-01',
            'session_name' => 'Morning',
            'created_by' => \App\Models\User::factory()->create(['role' => 'teacher'])->id,
        ]);

        \App\Models\AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $student->id,
            'status' => 'present',
        ]);

        $session2 = \App\Models\AttendanceSession::create([
            'classroom_id' => $this->classroom->id,
            'attendance_date' => '2025-10-02',
            'session_name' => 'Afternoon',
            'created_by' => \App\Models\User::factory()->create(['role' => 'teacher'])->id,
        ]);

        \App\Models\AttendanceRecord::create([
            'attendance_session_id' => $session2->id,
            'student_id' => $student->id,
            'status' => 'absent',
        ]);

        $this->assertCount(2, $student->attendanceRecords);
    }

    public function test_student_has_many_scores(): void
    {
        $student = \App\Models\Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'STU011',
            'full_name' => 'Score Student',
            'status' => 'studying',
        ]);

        \App\Models\Subject::create(['name' => 'Math', 'code' => 'MATH', 'is_active' => true]);
        $teacher = \App\Models\User::factory()->create(['role' => 'teacher']);

        $exam = \App\Models\Exam::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => 1,
            'teacher_id' => $teacher->id,
            'title' => 'Final',
            'exam_type' => 'final',
            'exam_date' => '2025-12-01',
            'max_score' => 10.00,
            'is_published' => true,
        ]);

        \App\Models\Score::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'score' => 8.50,
            'comment' => 'Good',
        ]);

        $this->assertCount(1, $student->scores);
    }
}
