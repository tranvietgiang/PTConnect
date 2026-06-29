<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class AttendanceSessionTest extends TestCase
{
    private AttendanceSession $attendanceSession;
    private Classroom $classroom;
    private User $teacher;

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

        $this->teacher = User::factory()->create(['role' => 'teacher']);

        $this->attendanceSession = AttendanceSession::create([
            'classroom_id' => $this->classroom->id,
            'attendance_date' => '2025-10-01',
            'session_name' => 'Morning',
            'created_by' => $this->teacher->id,
            'note' => 'Test session',
        ]);
    }

    public function test_attendance_session_has_fillable_attributes(): void
    {
        $session = new AttendanceSession();

        $this->assertEquals([
            'classroom_id',
            'attendance_date',
            'lesson_number',
            'session_name',
            'created_by',
            'note',
        ], $session->getFillable());
    }

    public function test_attendance_session_has_casts(): void
    {
        $session = new AttendanceSession();
        $casts = $session->getCasts();

        $this->assertTrue($casts['attendance_date'] === 'date');
        $this->assertTrue($casts['lesson_number'] === 'integer');
    }

    public function test_attendance_session_belongs_to_classroom(): void
    {
        $this->assertTrue($this->attendanceSession->classroom()->exists());
        $this->assertEquals($this->classroom->id, $this->attendanceSession->classroom->id);
    }

    public function test_attendance_session_belongs_to_creator(): void
    {
        $this->assertTrue($this->attendanceSession->creator()->exists());
        $this->assertEquals($this->teacher->id, $this->attendanceSession->creator->id);
    }

    public function test_attendance_session_has_many_attendance_records(): void
    {
        $student = Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'STU001',
            'full_name' => 'Test Student',
            'status' => 'studying',
        ]);

        AttendanceRecord::create([
            'attendance_session_id' => $this->attendanceSession->id,
            'student_id' => $student->id,
            'status' => 'present',
        ]);

        $student2 = Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'STU002',
            'full_name' => 'Test Student 2',
            'status' => 'studying',
        ]);

        AttendanceRecord::create([
            'attendance_session_id' => $this->attendanceSession->id,
            'student_id' => $student2->id,
            'status' => 'absent',
        ]);

        $this->assertCount(2, $this->attendanceSession->attendanceRecords);
    }

    public function test_attendance_session_can_be_created(): void
    {
        $this->assertDatabaseHas('attendance_sessions', [
            'classroom_id' => $this->classroom->id,
            'lesson_number' => 1,
            'session_name' => 'Morning',
            'created_by' => $this->teacher->id,
        ]);
    }
}
