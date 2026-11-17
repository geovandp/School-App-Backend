<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel ini merupakan snapshot penerima tugas pada saat tugas dipublikasikan.
     * Data siswa diambil dari student_enrollments yang aktif pada saat publikasi.
     *
     * KONTRAK dengan Student role:
     *   - Student membaca tabel ini untuk mengetahui tugas yang diterima.
     *   - Status: assigned = perlu dikerjakan, excused = dibebaskan, canceled = dibatalkan.
     */
    public function up(): void
    {
        Schema::create('assignment_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')
                ->constrained('assignments')
                ->cascadeOnDelete();
            $table->foreignId('student_id')
                ->comment('FK ke students.user_id')
                ->constrained('students', 'user_id')
                ->restrictOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->enum('status', ['assigned', 'excused', 'canceled'])->default('assigned');
            $table->timestamps();

            $table->unique(['assignment_id', 'student_id'], 'unique_assignment_recipient');
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_recipients');
    }
};
