<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class AssignmentTest extends TestCase
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
            'full_name' => 'Student',
            'status' => 'studying',
        ]);
    }

    public function test_assignment_can_be_created(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id,
            'title' => 'Bài tập về nhà số 1',
            'description' => 'Giải các bài tập trong SGK trang 10',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('assignments', [
            'title' => 'Bài tập về nhà số 1',
            'status' => 'published',
        ]);
    }

    public function test_assignment_has_fillable_attributes(): void
    {
        $assignment = new Assignment();

        $this->assertEquals([
            'created_by',
            'title',
            'description',
            'classroom_id',
            'grade_level',
            'due_date',
            'attachment_path',
            'attachment_name',
            'attachment_mime',
            'status',
        ], $assignment->getFillable());
    }

    public function test_assignment_belongs_to_classroom(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id,
            'title' => 'Assignment',
            'classroom_id' => $this->classroom->id,
            'status' => 'published',
        ]);

        $this->assertTrue($assignment->classroom()->exists());
        $this->assertSame($this->classroom->id, $assignment->classroom->id);
    }

    public function test_assignment_belongs_to_creator(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id,
            'title' => 'Assignment',
            'status' => 'published',
        ]);

        $this->assertTrue($assignment->creator()->exists());
        $this->assertSame($this->teacher->id, $assignment->creator->id);
    }

    public function test_assignment_has_many_submissions(): void
    {
        $assignment = Assignment::create([
            'created_by' => $this->teacher->id,
            'title' => 'Assignment',
            'status' => 'published',
        ]);

        AssignmentSubmission::create([
            'assignment_id' => $assignment->id,
            'student_id' => $this->student->id,
            'submitted_file_path' => 'path/to/file.pdf',
            'submitted_file_name' => 'file.pdf',
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        $this->assertCount(1, $assignment->submissions);
    }
}
