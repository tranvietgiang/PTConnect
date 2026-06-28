<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-05-31',
            'is_active' => true,
        ]);
    }

    public function test_classroom_can_be_created(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'description' => 'Lớp 10 chuyên Toán',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('classrooms', [
            'name' => '10A1',
            'grade_level' => 10,
        ]);
    }

    public function test_classroom_has_fillable_attributes(): void
    {
        $classroom = new Classroom();

        $this->assertEquals([
            'academic_year_id',
            'name',
            'grade_level',
            'description',
            'is_active',
        ], $classroom->getFillable());
    }

    public function test_classroom_belongs_to_academic_year(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '11A1',
            'grade_level' => 11,
            'is_active' => true,
        ]);

        $this->assertTrue($classroom->academicYear()->exists());
        $this->assertSame($this->academicYear->id, $classroom->academicYear->id);
    }

    public function test_classroom_has_many_students(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '12A1',
            'grade_level' => 12,
            'is_active' => true,
        ]);

        Student::create([
            'classroom_id' => $classroom->id,
            'student_code' => 'STU001',
            'full_name' => 'Nguyen Van A',
            'status' => 'studying',
        ]);

        Student::create([
            'classroom_id' => $classroom->id,
            'student_code' => 'STU002',
            'full_name' => 'Nguyen Van B',
            'status' => 'studying',
        ]);

        $this->assertCount(2, $classroom->students);
    }

    public function test_classroom_has_many_users(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $classroom->users()->attach($user, ['role_in_class' => 'teacher']);

        $this->assertCount(1, $classroom->users);
    }

    public function test_classroom_grade_level_is_integer(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'is_active' => true,
        ]);

        $this->assertIsInt($classroom->grade_level);
    }

    public function test_classroom_has_many_attendance_sessions(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'AttSessionRoom',
            'grade_level' => 10,
            'is_active' => true,
        ]);

        \App\Models\AttendanceSession::create([
            'classroom_id' => $classroom->id,
            'attendance_date' => '2025-10-01',
            'session_name' => 'Morning',
            'created_by' => \App\Models\User::factory()->create(['role' => 'teacher'])->id,
        ]);

        \App\Models\AttendanceSession::create([
            'classroom_id' => $classroom->id,
            'attendance_date' => '2025-10-02',
            'session_name' => 'Afternoon',
            'created_by' => \App\Models\User::factory()->create(['role' => 'teacher'])->id,
        ]);

        $this->assertCount(2, $classroom->attendanceSessions);
    }

    public function test_classroom_has_many_exams(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'ExamRoom',
            'grade_level' => 10,
            'is_active' => true,
        ]);

        \App\Models\Subject::create([
            'name' => 'Math',
            'code' => 'MATH',
            'is_active' => true,
        ]);

        \App\Models\Exam::create([
            'classroom_id' => $classroom->id,
            'subject_id' => 1,
            'teacher_id' => \App\Models\User::factory()->create(['role' => 'teacher'])->id,
            'title' => 'Midterm',
            'exam_type' => 'midterm',
            'exam_date' => '2025-11-01',
            'max_score' => 10.00,
            'is_published' => false,
        ]);

        $this->assertCount(1, $classroom->exams);
    }
}
