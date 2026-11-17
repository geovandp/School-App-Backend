<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teaching_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')
                ->constrained('classrooms')
                ->restrictOnDelete();
            $table->foreignId('teacher_id')
                ->constrained('teachers')
                ->restrictOnDelete();
            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Satu guru hanya boleh mengajar satu mapel di satu kelas yang sama
            $table->unique(['classroom_id', 'teacher_id', 'course_id'], 'unique_teaching_assignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_assignments');
    }
};
