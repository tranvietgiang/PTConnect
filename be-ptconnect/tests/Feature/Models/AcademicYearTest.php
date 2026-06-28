<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\Classroom;
use Tests\TestCase;

class AcademicYearTest extends TestCase
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

    public function test_academic_year_has_fillable_attributes(): void
    {
        $academicYear = new AcademicYear();

        $this->assertEquals([
            'name',
            'start_date',
            'end_date',
            'is_active',
        ], $academicYear->getFillable());
    }

    public function test_academic_year_has_casts(): void
    {
        $academicYear = new AcademicYear();
        $casts = $academicYear->getCasts();

        $this->assertTrue($casts['start_date'] === 'date');
        $this->assertTrue($casts['end_date'] === 'date');
        $this->assertTrue($casts['is_active'] === 'boolean');
    }

    public function test_academic_year_has_many_classrooms(): void
    {
        Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'is_active' => true,
        ]);

        Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '11B2',
            'grade_level' => 11,
            'is_active' => true,
        ]);

        $this->assertCount(2, $this->academicYear->classrooms);
    }

    public function test_academic_year_can_be_created(): void
    {
        $this->assertDatabaseHas('academic_years', [
            'name' => '2025-2026',
            'is_active' => true,
        ]);
    }
}
