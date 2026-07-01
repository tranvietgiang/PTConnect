<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('grade_level');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->string('attachment_name', 100)->nullable();
            $table->string('attachment_mime', 20)->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
