<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->index();
            $table->unsignedTinyInteger('grade_level')->index();
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->enum('status', ['active', 'completed', 'inactive'])->default('active')->index();
            $table->timestamps();

            $table->index(['grade_level', 'status']);
            $table->index(['status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
