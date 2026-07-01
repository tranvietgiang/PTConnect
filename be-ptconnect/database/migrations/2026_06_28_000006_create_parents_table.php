<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('full_name', 100);
            $table->string('email', 191)->index();
            $table->string('phone', 20)->nullable()->index();
            $table->string('relationship', 50);
            $table->string('address', 255)->nullable();
            $table->timestamps();

            $table->index(['student_id', 'relationship']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parents');
    }
};
