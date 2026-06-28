<?php

namespace Tests\Feature\Models;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\ParentProfile;
use App\Models\Student;
use App\Models\User;
use Tests\TestCase;

class ParentProfileTest extends TestCase
{
    private ParentProfile $parentProfile;
    private User $user;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'parent']);

        $this->parentProfile = ParentProfile::create([
            'user_id' => $this->user->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '0123456789',
            'relationship' => 'father',
            'address' => '123 Main St',
        ]);

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

        $this->student = Student::create([
            'classroom_id' => $classroom->id,
            'student_code' => 'STU001',
            'full_name' => 'Child Student',
            'status' => 'studying',
        ]);
    }

    public function test_parent_profile_has_fillable_attributes(): void
    {
        $profile = new ParentProfile();

        $this->assertEquals([
            'user_id',
            'full_name',
            'email',
            'phone',
            'relationship',
            'address',
        ], $profile->getFillable());
    }

    public function test_parent_profile_belongs_to_user(): void
    {
        $this->assertTrue($this->parentProfile->user()->exists());
        $this->assertEquals($this->user->id, $this->parentProfile->user->id);
    }

    public function test_parent_profile_belongs_to_many_students(): void
    {
        $this->parentProfile->students()->attach($this->student->id, ['is_primary' => true]);

        $this->assertTrue($this->parentProfile->students()->exists());
        $this->assertCount(1, $this->parentProfile->students);
    }

    public function test_parent_profile_has_many_notifications(): void
    {
        $sender = User::factory()->create(['role' => 'teacher']);

        Notification::create([
            'title' => 'Notification 1',
            'content' => 'Content 1',
            'type' => 'info',
            'sender_id' => $sender->id,
            'parent_id' => $this->parentProfile->id,
            'target_type' => 'all',
        ]);

        Notification::create([
            'title' => 'Notification 2',
            'content' => 'Content 2',
            'type' => 'warning',
            'sender_id' => $sender->id,
            'parent_id' => $this->parentProfile->id,
            'target_type' => 'all',
        ]);

        $this->assertCount(2, $this->parentProfile->notifications);
    }

    public function test_parent_profile_has_many_notification_recipients(): void
    {
        $sender = User::factory()->create(['role' => 'teacher']);

        $notification = Notification::create([
            'title' => 'Test',
            'content' => 'Test',
            'type' => 'info',
            'sender_id' => $sender->id,
            'target_type' => 'all',
        ]);

        NotificationRecipient::create([
            'notification_id' => $notification->id,
            'parent_id' => $this->parentProfile->id,
            'email' => 'recipient@test.com',
            'status' => 'sent',
        ]);

        NotificationRecipient::create([
            'notification_id' => $notification->id,
            'parent_id' => $this->parentProfile->id,
            'email' => 'other@test.com',
            'status' => 'pending',
        ]);

        $this->assertCount(2, $this->parentProfile->notificationRecipients);
    }

    public function test_parent_profile_can_be_created(): void
    {
        $this->assertDatabaseHas('parents', [
            'user_id' => $this->user->id,
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
            'relationship' => 'father',
        ]);
    }
}
