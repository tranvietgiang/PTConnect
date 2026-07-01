<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('grade_level')->nullable()->index();
            $table->date('due_date')->nullable()->index();
            $table->string('attachment_path', 255)->nullable();
            $table->string('attachment_name', 100)->nullable();
            $table->string('attachment_mime', 100)->nullable();
            $table->string('status', 20)->default('published')->index();
            $table->timestamps();

            $table->index(['classroom_id', 'grade_level', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
