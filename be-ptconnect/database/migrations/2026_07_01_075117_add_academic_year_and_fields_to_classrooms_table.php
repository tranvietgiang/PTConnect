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
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->foreignId('academic_year_id')->nullable()->constrained()->nullOnDelete()->after('course_id');
            $table->unsignedTinyInteger('grade_level')->nullable()->after('teacher_id');
            $table->date('start_date')->nullable()->after('grade_level');
            $table->date('end_date')->nullable()->after('start_date');
            $table->unsignedSmallInteger('total_lessons')->default(1)->after('end_date');
            $table->text('description')->nullable()->after('total_lessons');
            $table->boolean('is_active')->default(true)->after('status');

            $table->index(['academic_year_id', 'name']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table): void {
            $table->dropColumn(['academic_year_id', 'grade_level', 'start_date', 'end_date', 'total_lessons', 'description', 'is_active']);
        });
    }
};
