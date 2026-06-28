<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class AssignmentSubmissionTest extends TestCase
{
    private User $teacher;
    private Classroom $classroom;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);

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

        $this->student = Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'STU001',
            'full_name' => 'Test Student',
            'status' => 'studying',
        ]);
    }

    private function makeAssignment(): Assignment
    {
        return Assignment::create([
            'created_by' => $this->teacher->id,
            'title' => 'Test Assignment',
            'status' => 'published',
        ]);
    }

    public function test_submission_can_be_created(): void
    {
        $assignment = $this->makeAssignment();

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'submitted_file_path' => 'assignments/submissions/test.pdf',
            'submitted_file_name' => 'test.pdf',
            'submitted_file_mime' => 'application/pdf',
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->assertDatabaseHas('assignment_submissions', [
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
        ]);
    }

    public function test_submission_belongs_to_assignment(): void
    {
        $assignment = $this->makeAssignment();

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'submitted_file_path' => 'path.pdf',
            'submitted_file_name' => 'file.pdf',
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->assertTrue($submission->assignment()->exists());
        $this->assertSame($assignment->id, $submission->assignment->id);
    }

    public function test_submission_belongs_to_student(): void
    {
        $assignment = $this->makeAssignment();

        $submission = AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'submitted_file_path' => 'path.pdf',
            'submitted_file_name' => 'file.pdf',
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->assertTrue($submission->student()->exists());
        $this->assertSame($this->student->id, $submission->student->id);
    }
}
