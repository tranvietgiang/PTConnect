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

class ScoreTest extends TestCase
{
    private Score $score;
    private Exam $exam;
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

        $subject = Subject::create([
            'name' => 'Math',
            'code' => 'MATH',
            'is_active' => true,
        ]);

        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->exam = Exam::create([
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Midterm Exam',
            'exam_type' => 'midterm',
            'exam_date' => '2025-12-15',
            'max_score' => 100.00,
            'is_published' => false,
        ]);

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'student_code' => 'STU001',
            'full_name' => 'Test Student',
            'status' => 'studying',
        ]);

        $this->score = Score::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'score' => 85.50,
            'comment' => 'Good work',
            'email_sent_at' => now(),
        ]);
    }

    public function test_score_has_fillable_attributes(): void
    {
        $score = new Score();

        $this->assertEquals([
            'exam_id',
            'student_id',
            'score',
            'comment',
            'email_sent_at',
        ], $score->getFillable());
    }

    public function test_score_has_casts(): void
    {
        $score = new Score();
        $casts = $score->getCasts();

        $this->assertTrue($casts['score'] === 'decimal:2');
        $this->assertTrue($casts['email_sent_at'] === 'datetime');
    }

    public function test_score_belongs_to_exam(): void
    {
        $this->assertTrue($this->score->exam()->exists());
        $this->assertEquals($this->exam->id, $this->score->exam->id);
    }

    public function test_score_belongs_to_student(): void
    {
        $this->assertTrue($this->score->student()->exists());
        $this->assertEquals($this->student->id, $this->score->student->id);
    }

    public function test_score_can_be_created(): void
    {
        $this->assertDatabaseHas('scores', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'score' => 85.50,
        ]);
    }
}
