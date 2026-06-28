<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\User;
use Tests\TestCase;

class SubjectTest extends TestCase
{
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = Subject::create([
            'name' => 'Mathematics',
            'code' => 'MATH',
            'description' => 'Advanced Mathematics',
            'is_active' => true,
        ]);
    }

    public function test_subject_has_fillable_attributes(): void
    {
        $subject = new Subject();

        $this->assertEquals([
            'name',
            'code',
            'description',
            'is_active',
        ], $subject->getFillable());
    }

    public function test_subject_has_casts(): void
    {
        $subject = new Subject();
        $casts = $subject->getCasts();

        $this->assertTrue($casts['is_active'] === 'boolean');
    }

    public function test_subject_has_many_exams(): void
    {
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

        Exam::create([
            'classroom_id' => $classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Midterm',
            'exam_type' => 'midterm',
            'exam_date' => '2025-12-15',
            'max_score' => 100.00,
            'is_published' => false,
        ]);

        Exam::create([
            'classroom_id' => $classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $teacher->id,
            'title' => 'Final',
            'exam_type' => 'final',
            'exam_date' => '2026-05-20',
            'max_score' => 100.00,
            'is_published' => false,
        ]);

        $this->assertCount(2, $this->subject->exams);
    }

    public function test_subject_can_be_created(): void
    {
        $this->assertDatabaseHas('subjects', [
            'name' => 'Mathematics',
            'code' => 'MATH',
            'is_active' => true,
        ]);
    }
}
