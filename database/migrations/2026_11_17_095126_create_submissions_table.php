<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * KONTRAK BERSAMA ROLE TEACHER & STUDENT:
     * - Submissions dibuat oleh Student saat mengumpulkan tugas.
     * - assignment_recipient_id menghubungkan submission dengan penugasan spesifik siswa.
     * - attempt_number mencatat pengulangan (resubmission).
     * - status: draft, submitted, late, reviewed, returned.
     */
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_recipient_id')
                ->constrained('assignment_recipients')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('attempt_number')->default(1);
            $table->text('answer_text')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['draft', 'submitted', 'late', 'reviewed', 'returned'])->default('draft');
            $table->timestamps();

            $table->unique(['assignment_recipient_id', 'attempt_number'], 'unique_recipient_attempt');
            $table->index(['assignment_recipient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
