<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table): void {
            $table->string('email_status', 20)->default('not_sent')->index()->after('teacher_comment');
            $table->timestamp('score_emailed_at')->nullable()->after('email_status');
            $table->foreignId('email_sent_by')->nullable()->constrained('users')->nullOnDelete()->after('score_emailed_at');
            $table->text('email_error')->nullable()->after('email_sent_by');
        });
    }

    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table): void {
            $table->dropColumn(['email_status', 'score_emailed_at', 'email_sent_by', 'email_error']);
        });
    }
};
