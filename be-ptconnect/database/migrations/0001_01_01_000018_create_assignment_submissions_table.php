<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->string('submitted_file_path', 255)->nullable();
            $table->string('submitted_file_name', 100)->nullable();
            $table->string('submitted_file_mime', 20)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('teacher_comment')->nullable();
            $table->string('email_status', 20)->nullable();
            $table->timestamp('score_emailed_at')->nullable();
            $table->foreignId('email_sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('email_error')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id'], 'submission_unique');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
