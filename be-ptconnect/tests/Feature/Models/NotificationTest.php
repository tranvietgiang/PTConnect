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

class NotificationTest extends TestCase
{
    private Notification $notification;
    private User $sender;
    private Classroom $classroom;
    private Student $student;
    private ParentProfile $parentProfile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sender = User::factory()->create(['role' => 'teacher']);

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

        $parentUser = User::factory()->create(['role' => 'parent']);

        $this->parentProfile = ParentProfile::create([
            'user_id' => $parentUser->id,
            'student_id' => $this->student->id,
            'full_name' => 'Parent Name',
            'email' => 'parent@test.com',
            'relationship' => 'father',
        ]);

        $this->notification = Notification::create([
            'title' => 'Test Notification',
            'content' => 'This is a test notification.',
            'type' => 'info',
            'sender_id' => $this->sender->id,
            'target_type' => 'classroom',
            'classroom_id' => $this->classroom->id,
            'grade_level' => 10,
            'student_id' => $this->student->id,
            'parent_id' => $this->parentProfile->id,
        ]);
    }

    public function test_notification_has_fillable_attributes(): void
    {
        $notification = new Notification();

        $this->assertEquals([
            'title',
            'content',
            'type',
            'sender_id',
            'target_type',
            'classroom_id',
            'grade_level',
            'student_id',
            'parent_id',
        ], $notification->getFillable());
    }

    public function test_notification_has_casts(): void
    {
        $notification = new Notification();
        $casts = $notification->getCasts();

        $this->assertTrue($casts['grade_level'] === 'integer');
    }

    public function test_notification_belongs_to_sender(): void
    {
        $this->assertTrue($this->notification->sender()->exists());
        $this->assertEquals($this->sender->id, $this->notification->sender->id);
    }

    public function test_notification_belongs_to_classroom(): void
    {
        $this->assertTrue($this->notification->classroom()->exists());
        $this->assertEquals($this->classroom->id, $this->notification->classroom->id);
    }

    public function test_notification_belongs_to_student(): void
    {
        $this->assertTrue($this->notification->student()->exists());
        $this->assertEquals($this->student->id, $this->notification->student->id);
    }

    public function test_notification_belongs_to_parent_profile(): void
    {
        $this->assertTrue($this->notification->parentProfile()->exists());
        $this->assertEquals($this->parentProfile->id, $this->notification->parentProfile->id);
    }

    public function test_notification_has_many_recipients(): void
    {
        NotificationRecipient::create([
            'notification_id' => $this->notification->id,
            'parent_id' => $this->parentProfile->id,
            'student_id' => $this->student->id,
            'email' => 'recipient@test.com',
            'status' => 'sent',
        ]);

        NotificationRecipient::create([
            'notification_id' => $this->notification->id,
            'parent_id' => $this->parentProfile->id,
            'email' => 'other@test.com',
            'status' => 'pending',
        ]);

        $this->assertCount(2, $this->notification->recipients);
    }

    public function test_notification_can_be_created(): void
    {
        $this->assertDatabaseHas('notifications', [
            'title' => 'Test Notification',
            'sender_id' => $this->sender->id,
            'target_type' => 'classroom',
        ]);
    }
}
