<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('score_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('score_column_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('email_status', 20)->default('pending');
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['score_column_id', 'student_id'], 'score_record_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_records');
    }
};
