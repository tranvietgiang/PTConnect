<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('type')->index();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('target_type')->index();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('grade_level')->nullable()->index();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('parents')->nullOnDelete();
            $table->timestamps();

            $table->index(['sender_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
