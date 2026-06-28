<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class AttendanceRecordTest extends TestCase
{
    private AttendanceRecord $attendanceRecord;
    private AttendanceSession $attendanceSession;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $academicYear = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-05-31',
            'is_active' => true,
        ]);

        $classroom = Classroom::create([
            'academic_year_id' => $academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'is_active' => true,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->attendanceSession = AttendanceSession::create([
            'classroom_id' => $classroom->id,
            'attendance_date' => '2025-10-01',
            'session_name' => 'Morning',
            'created_by' => $teacher->id,
        ]);

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'student_code' => 'STU001',
            'full_name' => 'Test Student',
            'status' => 'studying',
        ]);

        $this->attendanceRecord = AttendanceRecord::create([
            'attendance_session_id' => $this->attendanceSession->id,
            'student_id' => $this->student->id,
            'status' => 'present',
            'late_minutes' => 5,
            'note' => 'On time',
            'email_sent_at' => now(),
        ]);
    }

    public function test_attendance_record_has_fillable_attributes(): void
    {
        $record = new AttendanceRecord();

        $this->assertEquals([
            'attendance_session_id',
            'student_id',
            'status',
            'late_minutes',
            'note',
            'email_sent_at',
        ], $record->getFillable());
    }

    public function test_attendance_record_has_casts(): void
    {
        $record = new AttendanceRecord();
        $casts = $record->getCasts();

        $this->assertTrue($casts['late_minutes'] === 'integer');
        $this->assertTrue($casts['email_sent_at'] === 'datetime');
    }

    public function test_attendance_record_belongs_to_attendance_session(): void
    {
        $this->assertTrue($this->attendanceRecord->attendanceSession()->exists());
        $this->assertEquals($this->attendanceSession->id, $this->attendanceRecord->attendanceSession->id);
    }

    public function test_attendance_record_belongs_to_student(): void
    {
        $this->assertTrue($this->attendanceRecord->student()->exists());
        $this->assertEquals($this->student->id, $this->attendanceRecord->student->id);
    }

    public function test_attendance_record_can_be_created(): void
    {
        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $this->attendanceSession->id,
            'student_id' => $this->student->id,
            'status' => 'present',
            'late_minutes' => 5,
        ]);
    }
}
