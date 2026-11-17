<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')
                ->constrained('submissions')
                ->cascadeOnDelete();
            $table->foreignId('reviewed_by')
                ->comment('FK ke teachers.id — guru yang menilai')
                ->constrained('teachers')
                ->restrictOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->enum('status', ['reviewed', 'returned', 'approved'])->default('reviewed');
            $table->timestamps();

            $table->index('submission_id');
            $table->index('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_reviews');
    }
};
