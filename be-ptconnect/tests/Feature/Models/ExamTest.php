<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Tests\TestCase;

class ExamTest extends TestCase
{
    private Exam $exam;
    private Classroom $classroom;
    private Subject $subject;
    private User $teacher;
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

        $this->classroom = Classroom::create([
            'academic_year_id' => $academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'is_active' => true,
        ]);

        $this->subject = Subject::create([
            'name' => 'Math',
            'code' => 'MATH',
            'is_active' => true,
        ]);

        $this->teacher = User::factory()->create(['role' => 'teacher']);

        $this->exam = Exam::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Midterm Exam',
            'exam_type' => 'midterm',
            'exam_date' => '2025-12-15',
            'max_score' => 100.00,
            'note' => 'Important exam',
            'is_published' => false,
        ]);

        $this->student = Student::create([
            'classroom_id' => $this->classroom->id,
            'student_code' => 'STU001',
            'full_name' => 'Test Student',
            'status' => 'studying',
        ]);
    }

    public function test_exam_has_fillable_attributes(): void
    {
        $exam = new Exam();

        $this->assertEquals([
            'classroom_id',
            'subject_id',
            'teacher_id',
            'title',
            'exam_type',
            'exam_date',
            'max_score',
            'note',
            'is_published',
            'published_at',
        ], $exam->getFillable());
    }

    public function test_exam_has_casts(): void
    {
        $exam = new Exam();
        $casts = $exam->getCasts();

        $this->assertTrue($casts['exam_date'] === 'date');
        $this->assertTrue($casts['max_score'] === 'decimal:2');
        $this->assertTrue($casts['is_published'] === 'boolean');
        $this->assertTrue($casts['published_at'] === 'datetime');
    }

    public function test_exam_belongs_to_classroom(): void
    {
        $this->assertTrue($this->exam->classroom()->exists());
        $this->assertEquals($this->classroom->id, $this->exam->classroom->id);
    }

    public function test_exam_belongs_to_subject(): void
    {
        $this->assertTrue($this->exam->subject()->exists());
        $this->assertEquals($this->subject->id, $this->exam->subject->id);
    }

    public function test_exam_belongs_to_teacher(): void
    {
        $this->assertTrue($this->exam->teacher()->exists());
        $this->assertEquals($this->teacher->id, $this->exam->teacher->id);
    }

    public function test_exam_has_many_scores(): void
    {
        Score::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'score' => 85.50,
            'comment' => 'Good job',
        ]);

        $this->assertCount(1, $this->exam->scores);
        $this->assertTrue($this->exam->scores()->exists());
    }

    public function test_exam_can_be_created(): void
    {
        $this->assertDatabaseHas('exams', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Midterm Exam',
        ]);
    }
}
