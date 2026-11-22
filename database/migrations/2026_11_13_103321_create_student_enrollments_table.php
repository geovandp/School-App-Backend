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
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained('students', 'id')
                ->cascadeOnDelete();
            $table->foreignId('classroom_id')
                ->constrained('classrooms', 'id')
                ->restrictOnDelete();
            $table->date('enrollment_date');
            $table->enum('status', [
                'active',
                'completed',
                'transferred',
                'withdrawn',
            ])->default('active');
            $table->date('exit_date')->nullable();
            $table->text('notes')->nullable();
            $table->index(
                ['student_id', 'status'],
                'student_enrollments_student_status_index'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
