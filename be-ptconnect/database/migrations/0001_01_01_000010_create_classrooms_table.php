<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assistant_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 200);
            $table->unsignedTinyInteger('grade_level');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedSmallInteger('total_lessons')->nullable();
            $table->json('study_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedSmallInteger('max_students')->nullable();
            $table->string('status', 20)->default('upcoming');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamp('assistant_access_locked_at')->nullable();
            $table->timestamps();

            $table->index('grade_level');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
