<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')
                ->constrained('teaching_assignments')
                ->restrictOnDelete();
            $table->foreignId('created_by')
                ->comment('FK ke teachers.id — guru yang membuat tugas')
                ->constrained('teachers')
                ->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('assignment_type', ['individual', 'group'])->default('individual');
            $table->enum('target_type', ['all', 'specific'])->default('all')
                ->comment('all = seluruh siswa di kelas, specific = siswa tertentu');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('start_at')->nullable()->comment('Waktu tugas mulai bisa dikerjakan');
            $table->timestamp('due_at')->nullable()->comment('Deadline pengumpulan');
            $table->decimal('max_score', 5, 2)->default(100)->comment('Nilai maksimal');
            $table->boolean('allow_late_submission')->default(false);
            $table->unsignedTinyInteger('max_attempts')->default(1)->comment('Maksimal percobaan submit');
            $table->enum('status', ['draft', 'published', 'closed', 'archived'])->default('draft');
            $table->timestamps();

            $table->index(['teaching_assignment_id', 'status']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
