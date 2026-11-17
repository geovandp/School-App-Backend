<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')
                ->constrained('academic_years')
                ->restrictOnDelete();
            $table->unsignedTinyInteger('period_number')->comment('Urutan jam ke-N');
            $table->string('name', 50)->comment('Contoh: Jam 1, Istirahat 1');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['academic_year_id', 'period_number'], 'unique_period_per_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_periods');
    }
};
