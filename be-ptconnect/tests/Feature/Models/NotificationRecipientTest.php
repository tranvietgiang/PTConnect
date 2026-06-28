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

class NotificationRecipientTest extends TestCase
{
    private NotificationRecipient $recipient;
    private Notification $notification;
    private ParentProfile $parentProfile;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $sender = User::factory()->create(['role' => 'teacher']);

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
            'full_name' => 'Test Student',
            'status' => 'studying',
        ]);

        $parentUser = User::factory()->create(['role' => 'parent']);

        $this->parentProfile = ParentProfile::create([
            'user_id' => $parentUser->id,
            'full_name' => 'Parent Name',
            'email' => 'parent@test.com',
            'relationship' => 'father',
        ]);

        $this->notification = Notification::create([
            'title' => 'Test Notification',
            'content' => 'Content',
            'type' => 'info',
            'sender_id' => $sender->id,
            'target_type' => 'all',
        ]);

        $this->recipient = NotificationRecipient::create([
            'notification_id' => $this->notification->id,
            'parent_id' => $this->parentProfile->id,
            'student_id' => $this->student->id,
            'email' => 'recipient@test.com',
            'sent_at' => now(),
            'read_at' => now(),
            'status' => 'sent',
        ]);
    }

    public function test_notification_recipient_has_fillable_attributes(): void
    {
        $recipient = new NotificationRecipient();

        $this->assertEquals([
            'notification_id',
            'parent_id',
            'student_id',
            'email',
            'sent_at',
            'read_at',
            'status',
        ], $recipient->getFillable());
    }

    public function test_notification_recipient_has_casts(): void
    {
        $recipient = new NotificationRecipient();
        $casts = $recipient->getCasts();

        $this->assertTrue($casts['sent_at'] === 'datetime');
        $this->assertTrue($casts['read_at'] === 'datetime');
    }

    public function test_notification_recipient_belongs_to_notification(): void
    {
        $this->assertTrue($this->recipient->notification()->exists());
        $this->assertEquals($this->notification->id, $this->recipient->notification->id);
    }

    public function test_notification_recipient_belongs_to_parent_profile(): void
    {
        $this->assertTrue($this->recipient->parentProfile()->exists());
        $this->assertEquals($this->parentProfile->id, $this->recipient->parentProfile->id);
    }

    public function test_notification_recipient_belongs_to_student(): void
    {
        $this->assertTrue($this->recipient->student()->exists());
        $this->assertEquals($this->student->id, $this->recipient->student->id);
    }

    public function test_notification_recipient_can_be_created(): void
    {
        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $this->notification->id,
            'email' => 'recipient@test.com',
            'status' => 'sent',
        ]);
    }
}
