<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('status')->index();
            $table->unsignedInteger('late_minutes')->nullable()->default(0);
            $table->text('note')->nullable();
            $table->timestamp('email_sent_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'student_id'], 'attendance_student_unique');
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
