<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->cascadeOnDelete();
            $table->foreignId('skill_id')
                ->constrained('skills')
                ->restrictOnDelete();
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert'])
                ->default('beginner');
            $table->unsignedSmallInteger('years_experience')->nullable()->comment('Tahun pengalaman');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamps();

            // Satu guru tidak boleh mendaftarkan skill yang sama dua kali
            $table->unique(['teacher_id', 'skill_id'], 'unique_teacher_skill');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_skills');
    }
};
