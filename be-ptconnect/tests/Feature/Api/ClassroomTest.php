<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\User;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    private User $admin;
    private User $teacher;
    private User $assistant;
    private User $parent;
    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->assistant = User::factory()->create(['role' => 'assistant']);
        $this->parent = User::factory()->create(['role' => 'parent']);

        $this->academicYear = AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-05-31',
            'is_active' => true,
        ]);
    }

    private function authHeader(User $user): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        return ['Authorization' => 'Bearer ' . $response->json('data.access_token')];
    }

    public function test_admin_can_list_all_classes(): void
    {
        Classroom::create(['academic_year_id' => $this->academicYear->id, 'name' => '10A1', 'grade_level' => 10, 'is_active' => true]);
        Classroom::create(['academic_year_id' => $this->academicYear->id, 'name' => '11A1', 'grade_level' => 11, 'is_active' => true]);

        $response = $this->getJson('/api/classes', $this->authHeader($this->admin));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(2, 'data');
    }

    public function test_teacher_sees_only_assigned_classes(): void
    {
        $class1 = Classroom::create(['academic_year_id' => $this->academicYear->id, 'name' => '10A1', 'grade_level' => 10, 'is_active' => true]);
        Classroom::create(['academic_year_id' => $this->academicYear->id, 'name' => '11A1', 'grade_level' => 11, 'is_active' => true]);

        $class1->users()->attach($this->teacher->id, ['role_in_class' => 'teacher']);

        $response = $this->getJson('/api/classes', $this->authHeader($this->teacher));

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_list_classes(): void
    {
        $response = $this->getJson('/api/classes');

        $response->assertStatus(401);
    }

    public function test_admin_can_create_class(): void
    {
        $response = $this->postJson('/api/classes', [
            'name' => '12A1',
            'grade_level' => 12,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'total_lessons' => 30,
            'description' => 'Lop 12 chuyen Toan',
        ], $this->authHeader($this->admin));

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Class created.',
            ])
            ->assertJsonStructure(['data' => ['id', 'name', 'grade_level', 'start_date', 'end_date', 'total_lessons']]);

        $this->assertDatabaseHas('classrooms', ['name' => '12A1', 'grade_level' => 12, 'total_lessons' => 30]);
    }

    public function test_admin_can_update_class_schedule(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'start_date' => '2026-06-01',
            'end_date' => '2026-12-31',
            'total_lessons' => 30,
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/classes/{$classroom->id}", [
            'name' => '10A1',
            'grade_level' => 10,
            'start_date' => '2026-07-01',
            'end_date' => '2026-11-30',
            'total_lessons' => 24,
            'description' => 'Updated',
        ], $this->authHeader($this->admin));

        $response->assertStatus(200)
            ->assertJsonPath('data.total_lessons', 24)
            ->assertJsonPath('data.start_date', '2026-07-01');

        $this->assertDatabaseHas('classrooms', ['id' => $classroom->id, 'total_lessons' => 24]);
    }

    public function test_teacher_cannot_create_class(): void
    {
        $response = $this->postJson('/api/classes', [
            'name' => '12A1',
            'grade_level' => 12,
        ], $this->authHeader($this->teacher));

        $response->assertStatus(403);
    }

    public function test_create_class_validates_grade_level(): void
    {
        $response = $this->postJson('/api/classes', [
            'name' => 'InvalidClass',
            'grade_level' => 13,
        ], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_create_class_requires_name(): void
    {
        $response = $this->postJson('/api/classes', [
            'grade_level' => 10,
        ], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_admin_can_show_class(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '10A1', 'grade_level' => 10, 'is_active' => true,
        ]);

        $response = $this->getJson("/api/classes/{$classroom->id}", $this->authHeader($this->admin));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.name', '10A1');
    }

    public function test_teacher_can_show_assigned_class(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '10A1', 'grade_level' => 10, 'is_active' => true,
        ]);
        $classroom->users()->attach($this->teacher->id, ['role_in_class' => 'teacher']);

        $response = $this->getJson("/api/classes/{$classroom->id}", $this->authHeader($this->teacher));

        $response->assertStatus(200);
    }

    public function test_teacher_cannot_show_unassigned_class(): void
    {
        $classroom = Classroom::create([
            'academic_year_id' => $this->academicYear->id,
            'name' => '10A1', 'grade_level' => 10, 'is_active' => true,
        ]);

        $response = $this->getJson("/api/classes/{$classroom->id}", $this->authHeader($this->teacher));

        $response->assertStatus(403);
    }
}
