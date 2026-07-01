<?php

namespace Tests\Feature\Api;

use App\Models\AcademicYear;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    private User $admin;
    private User $assistant;
    private User $teacher;
    private Classroom $classroom;
    private Student $student1;
    private Student $student2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->assistant = User::factory()->create(['role' => 'assistant']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);

        $academicYear = AcademicYear::create([
            'name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-05-31', 'is_active' => true,
        ]);

        $this->classroom = Classroom::create([
            'academic_year_id' => $academicYear->id, 'name' => '10A1', 'grade_level' => 10, 'total_lessons' => 3, 'is_active' => true,
        ]);
        $this->classroom->users()->attach($this->assistant->id, ['role_in_class' => 'assistant']);

        $this->student1 = Student::create([
            'classroom_id' => $this->classroom->id, 'student_code' => 'HS001', 'full_name' => 'Nguyen Van A',
            'status' => 'studying',
        ]);
        $this->student2 = Student::create([
            'classroom_id' => $this->classroom->id, 'student_code' => 'HS002', 'full_name' => 'Tran Van B',
            'status' => 'studying',
        ]);
    }

    private function authHeader(User $user): array
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email, 'password' => 'password',
        ]);

        return ['Authorization' => 'Bearer ' . $response->json('data.access_token')];
    }

    public function test_admin_can_get_today_attendance(): void
    {
        $response = $this->getJson('/api/attendance/today?classroom_id=' . $this->classroom->id, $this->authHeader($this->admin));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['classroom', 'session', 'records']]);
    }

    public function test_assistant_can_get_today_attendance(): void
    {
        $response = $this->getJson('/api/attendance/today?classroom_id=' . $this->classroom->id, $this->authHeader($this->assistant));

        $response->assertStatus(200);
    }

    public function test_teacher_cannot_get_today_attendance(): void
    {
        $response = $this->getJson('/api/attendance/today?classroom_id=' . $this->classroom->id, $this->authHeader($this->teacher));

        $response->assertStatus(403);
    }

    public function test_today_attendance_validates_classroom(): void
    {
        $response = $this->getJson('/api/attendance/today', $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_admin_can_submit_attendance(): void
    {
        $response = $this->postJson('/api/attendance', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => now()->toDateString(),
            'lesson_number' => 2,
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'present'],
                ['student_id' => $this->student2->id, 'status' => 'late', 'late_minutes' => 15],
            ],
        ], $this->authHeader($this->admin));

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'message' => 'Attendance submitted.'])
            ->assertJsonStructure(['data' => ['classroom', 'session', 'records']]);

        $this->assertDatabaseHas('attendance_sessions', [
            'classroom_id' => $this->classroom->id,
            'lesson_number' => 2,
        ]);
    }

    public function test_assistant_can_submit_attendance(): void
    {
        $response = $this->postJson('/api/attendance', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => now()->toDateString(),
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'present'],
            ],
        ], $this->authHeader($this->assistant));

        $response->assertStatus(201);
    }

    public function test_attendance_submit_validates_records(): void
    {
        $response = $this->postJson('/api/attendance', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => now()->toDateString(),
            'records' => [],
        ], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_attendance_submit_validates_status(): void
    {
        $response = $this->postJson('/api/attendance', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => now()->toDateString(),
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'invalid_status'],
            ],
        ], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_attendance_submit_rejects_lesson_outside_class_total_lessons(): void
    {
        $response = $this->postJson('/api/attendance', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => now()->toDateString(),
            'lesson_number' => 4,
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'present'],
            ],
        ], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_admin_can_manage_attendance_sessions(): void
    {
        $createResponse = $this->postJson('/api/attendance/sessions', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => '2026-06-29',
            'lesson_number' => 1,
            'session_name' => 'Lesson 1',
            'note' => 'Buoi hoc demo',
        ], $this->authHeader($this->admin));

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.lesson_number', 1)
            ->assertJsonPath('data.classroom_id', $this->classroom->id);

        $sessionId = $createResponse->json('data.id');

        $this->getJson('/api/attendance/sessions', $this->authHeader($this->admin))
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->putJson("/api/attendance/sessions/{$sessionId}", [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => '2026-06-30',
            'lesson_number' => 2,
            'session_name' => 'Lesson 2',
            'note' => 'Da sua',
        ], $this->authHeader($this->admin))
            ->assertStatus(200)
            ->assertJsonPath('data.lesson_number', 2)
            ->assertJsonPath('data.attendance_date', '2026-06-30');

        $this->deleteJson("/api/attendance/sessions/{$sessionId}", [], $this->authHeader($this->admin))
            ->assertStatus(200);

        $this->assertDatabaseMissing('attendance_sessions', ['id' => $sessionId]);
    }

    public function test_admin_can_bulk_create_attendance_sessions(): void
    {
        $response = $this->postJson('/api/attendance/sessions/bulk', [
            'classroom_id' => $this->classroom->id,
            'start_date' => '2026-06-29',
            'start_lesson_number' => 1,
            'lesson_count' => 3,
            'day_interval' => 7,
            'session_name_prefix' => 'Lesson',
        ], $this->authHeader($this->admin));

        $response->assertStatus(201)
            ->assertJsonPath('data.created_count', 3)
            ->assertJsonPath('data.skipped_count', 0);

        $this->assertDatabaseHas('attendance_sessions', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => '2026-07-13 00:00:00',
            'lesson_number' => 3,
        ]);
    }

    public function test_admin_can_view_attendance_session_student_statuses(): void
    {
        $session = AttendanceSession::create([
            'classroom_id' => $this->classroom->id,
            'attendance_date' => '2026-06-29',
            'lesson_number' => 1,
            'session_name' => 'Lesson 1',
            'created_by' => $this->admin->id,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $this->student1->id,
            'status' => 'absent',
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $session->id,
            'student_id' => $this->student2->id,
            'status' => 'present',
        ]);

        $response = $this->getJson("/api/attendance/sessions/{$session->id}", $this->authHeader($this->admin));

        $response->assertStatus(200)
            ->assertJsonPath('data.records.0.student_name', 'Nguyen Van A')
            ->assertJsonPath('data.records.0.status', 'absent')
            ->assertJsonPath('data.records.1.student_name', 'Tran Van B')
            ->assertJsonPath('data.records.1.status', 'present');
    }

    public function test_attendance_session_management_is_admin_only(): void
    {
        $this->getJson('/api/attendance/sessions', $this->authHeader($this->assistant))
            ->assertStatus(403);
    }

    public function test_admin_cannot_create_duplicate_attendance_session(): void
    {
        AttendanceSession::create([
            'classroom_id' => $this->classroom->id,
            'attendance_date' => '2026-06-29',
            'lesson_number' => 1,
            'session_name' => 'Lesson 1',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->postJson('/api/attendance/sessions', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => '2026-06-29',
            'lesson_number' => 1,
            'session_name' => 'Lesson 1',
        ], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_attendance_submit_rejects_student_not_in_class(): void
    {
        $otherClassroom = Classroom::create([
            'academic_year_id' => $this->classroom->academic_year_id, 'name' => '11A1', 'grade_level' => 11, 'is_active' => true,
        ]);
        $otherStudent = Student::create([
            'classroom_id' => $otherClassroom->id, 'student_code' => 'HS999', 'full_name' => 'Other', 'status' => 'studying',
        ]);

        $response = $this->postJson('/api/attendance', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => now()->toDateString(),
            'records' => [
                ['student_id' => $otherStudent->id, 'status' => 'present'],
            ],
        ], $this->authHeader($this->admin));

        $response->assertStatus(422);
    }

    public function test_admin_can_view_attendance_history(): void
    {
        $session = AttendanceSession::create([
            'classroom_id' => $this->classroom->id, 'attendance_date' => now()->toDateString(),
            'session_name' => 'Buoi hoc', 'created_by' => $this->admin->id,
        ]);
        AttendanceRecord::create([
            'attendance_session_id' => $session->id, 'student_id' => $this->student1->id, 'status' => 'present',
        ]);

        $response = $this->getJson('/api/attendance/history', $this->authHeader($this->admin));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_assistant_can_view_history(): void
    {
        $response = $this->getJson('/api/attendance/history', $this->authHeader($this->assistant));

        $response->assertStatus(200);
    }

    public function test_teacher_cannot_view_attendance_history(): void
    {
        $response = $this->getJson('/api/attendance/history', $this->authHeader($this->teacher));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_attendance(): void
    {
        $response = $this->getJson('/api/attendance/today?classroom_id=1');
        $response->assertStatus(401);
    }

    public function test_attendance_creates_notifications_for_late_students(): void
    {
        \App\Models\ParentProfile::create([
            'user_id' => User::factory()->create(['role' => 'parent'])->id,
            'student_id' => $this->student1->id,
            'full_name' => 'Phu huynh A', 'email' => 'parenta@test.com', 'relationship' => 'father',
        ]);
        \App\Models\ParentProfile::create([
            'user_id' => User::factory()->create(['role' => 'parent'])->id,
            'student_id' => $this->student2->id,
            'full_name' => 'Phu huynh B', 'email' => 'parentb@test.com', 'relationship' => 'mother',
        ]);

        $this->postJson('/api/attendance', [
            'classroom_id' => $this->classroom->id,
            'attendance_date' => now()->toDateString(),
            'records' => [
                ['student_id' => $this->student1->id, 'status' => 'present'],
                ['student_id' => $this->student2->id, 'status' => 'late', 'late_minutes' => 10],
            ],
        ], $this->authHeader($this->admin));

        $this->assertDatabaseHas('notifications', ['type' => 'attendance']);
        $this->assertDatabaseHas('email_logs', ['type' => 'attendance']);
        $this->assertDatabaseHas('email_logs', ['recipient_email' => 'parentb@test.com', 'type' => 'attendance']);
        $this->assertDatabaseMissing('email_logs', ['recipient_email' => 'parenta@test.com', 'type' => 'attendance']);
        $this->assertDatabaseMissing('notification_recipients', ['email' => 'parenta@test.com']);
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $this->student1->id,
            'status' => 'present',
            'email_sent_at' => null,
        ]);
    }
}
