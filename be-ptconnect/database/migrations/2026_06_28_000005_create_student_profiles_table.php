<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('student_code', 50)->unique();
            $table->string('full_name', 100)->index();
            $table->string('student_email', 191)->unique();
            $table->string('parent_email', 191)->index();
            $table->string('high_school_name', 100)->index();
            $table->string('cccd', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('student_phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('parent_phone', 20)->nullable();
            $table->string('parent_full_name', 100)->nullable();
            $table->string('parent_relation', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
