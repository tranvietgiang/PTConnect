<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignment_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('submitted_file_path', 255);
            $table->string('submitted_file_name', 100);
            $table->string('submitted_file_mime', 100)->nullable();
            $table->timestamp('submitted_at');
            $table->string('status', 20)->default('submitted')->index();
            $table->decimal('score', 4, 2)->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
    }
};
