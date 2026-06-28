<?php

namespace Tests\Feature\Models;

use App\Models\EmailLog;
use Tests\TestCase;

class EmailLogTest extends TestCase
{
    public function test_email_log_has_fillable_attributes(): void
    {
        $log = new EmailLog();

        $this->assertEquals([
            'recipient_email',
            'recipient_name',
            'subject',
            'content',
            'type',
            'status',
            'error_message',
            'sent_at',
            'related_type',
            'related_id',
        ], $log->getFillable());
    }

    public function test_email_log_has_casts(): void
    {
        $log = new EmailLog();
        $casts = $log->getCasts();

        $this->assertTrue($casts['sent_at'] === 'datetime');
    }

    public function test_email_log_can_be_created(): void
    {
        $log = EmailLog::create([
            'recipient_email' => 'test@example.com',
            'recipient_name' => 'Test User',
            'subject' => 'Welcome',
            'content' => 'Welcome to the system.',
            'type' => 'welcome',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->assertDatabaseHas('email_logs', [
            'recipient_email' => 'test@example.com',
            'subject' => 'Welcome',
            'status' => 'sent',
        ]);
    }

    public function test_email_log_can_have_error_message(): void
    {
        $log = EmailLog::create([
            'recipient_email' => 'fail@example.com',
            'recipient_name' => 'Fail User',
            'subject' => 'Failed',
            'content' => 'Test',
            'type' => 'test',
            'status' => 'failed',
            'error_message' => 'Connection timeout',
        ]);

        $this->assertSame('failed', $log->status);
        $this->assertSame('Connection timeout', $log->error_message);
    }
}
