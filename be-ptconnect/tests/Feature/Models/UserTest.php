<?php

namespace Tests\Feature\Models;

use App\Models\Classroom;
use App\Models\AcademicYear;
use App\Models\ParentProfile;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_user_can_be_created(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'username' => 'testuser',
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'username' => 'testuser',
            'role' => 'teacher',
        ]);
        $this->assertNotNull($user->password);
    }

    public function test_user_has_fillable_attributes(): void
    {
        $user = new User();

        $this->assertEquals([
            'name',
            'email',
            'username',
            'password',
            'role',
            'phone',
            'avatar',
            'is_active',
            'last_login_at',
        ], $user->getFillable());
    }

    public function test_user_password_is_hidden(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    public function test_user_email_is_visible(): void
    {
        $user = User::factory()->create(['email' => 'visible@example.com']);

        $this->assertSame('visible@example.com', $user->email);
    }

    public function test_user_has_casts(): void
    {
        $user = new User();
        $casts = $user->getCasts();

        $this->assertTrue($casts['is_active'] === 'boolean');
    }

    public function test_user_has_refresh_tokens_relation(): void
    {
        $user = User::factory()->create();

        RefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', 'test_token'),
            'jti' => (string) Str::uuid(),
            'expires_at' => now()->addDay(),
        ]);

        $this->assertTrue($user->refreshTokens()->exists());
        $this->assertCount(1, $user->refreshTokens);
    }

    public function test_user_has_parent_profile_relation(): void
    {
        $user = User::factory()->create(['role' => 'parent']);

        ParentProfile::create([
            'user_id' => $user->id,
            'full_name' => 'Parent Name',
            'email' => 'parent@example.com',
            'phone' => '0123456789',
            'relationship' => 'father',
        ]);

        $this->assertTrue($user->parentProfile()->exists());
    }

    public function test_user_has_classrooms_relation(): void
    {
        $user = User::factory()->create();
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

        $user->classrooms()->attach($classroom, ['role_in_class' => 'homeroom']);

        $this->assertTrue($user->classrooms()->exists());
        $this->assertCount(1, $user->classrooms);
    }

    public function test_user_role_can_be_admin(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->assertSame('admin', $user->role);
    }

    public function test_user_has_many_attendance_sessions(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'teacher']);
        $academicYear = \App\Models\AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-05-31',
            'is_active' => true,
        ]);
        $classroom = \App\Models\Classroom::create([
            'academic_year_id' => $academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'is_active' => true,
        ]);

        \App\Models\AttendanceSession::create([
            'classroom_id' => $classroom->id,
            'attendance_date' => '2025-10-01',
            'session_name' => 'Morning',
            'created_by' => $user->id,
        ]);

        $this->assertCount(1, $user->attendanceSessions);
    }

    public function test_user_has_many_exams(): void
    {
        $teacher = \App\Models\User::factory()->create(['role' => 'teacher']);
        $academicYear = \App\Models\AcademicYear::create([
            'name' => '2025-2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-05-31',
            'is_active' => true,
        ]);
        $classroom = \App\Models\Classroom::create([
            'academic_year_id' => $academicYear->id,
            'name' => '10A1',
            'grade_level' => 10,
            'is_active' => true,
        ]);
        \App\Models\Subject::create(['name' => 'Math', 'code' => 'MATH', 'is_active' => true]);

        \App\Models\Exam::create([
            'classroom_id' => $classroom->id,
            'subject_id' => 1,
            'teacher_id' => $teacher->id,
            'title' => 'Final',
            'exam_type' => 'final',
            'exam_date' => '2025-12-01',
            'max_score' => 10.00,
            'is_published' => true,
        ]);

        $this->assertCount(1, $teacher->exams);
    }

    public function test_user_has_many_sent_notifications(): void
    {
        $user = \App\Models\User::factory()->create(['role' => 'teacher']);

        \App\Models\Notification::create([
            'title' => 'Test Notification',
            'content' => 'Content',
            'type' => 'info',
            'sender_id' => $user->id,
            'target_type' => 'all',
        ]);

        \App\Models\Notification::create([
            'title' => 'Test Notification 2',
            'content' => 'Content 2',
            'type' => 'warning',
            'sender_id' => $user->id,
            'target_type' => 'all',
        ]);

        $this->assertCount(2, $user->sentNotifications);
    }
}
