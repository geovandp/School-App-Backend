<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_journal_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_journal_id')
                ->constrained('learning_journals')
                ->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size')->comment('Ukuran file dalam bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_journal_attachments');
    }
};
