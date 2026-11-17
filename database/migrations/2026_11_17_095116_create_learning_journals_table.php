<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_assignment_id')
                ->constrained('teaching_assignments')
                ->restrictOnDelete();
            $table->foreignId('created_by')
                ->comment('FK ke users.id — guru yang membuat jurnal')
                ->constrained('users')
                ->restrictOnDelete();
            $table->date('learning_date');
            $table->string('topic');
            $table->text('learning_objective')->nullable()->comment('Tujuan pembelajaran');
            $table->text('learning_material')->nullable()->comment('Materi yang disampaikan');
            $table->text('learning_activities')->nullable()->comment('Aktivitas pembelajaran');
            $table->text('learning_outcomes')->nullable()->comment('Hasil/capaian pembelajaran');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamps();

            $table->index(['teaching_assignment_id', 'learning_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_journals');
    }
};
