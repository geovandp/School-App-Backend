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
        Schema::create('school_calendar_days', function (Blueprint $table) {
            $table->id();
            $table->date('calendar_date')->unique();
            $table->enum('day_type', ['regular', 'weekend', 'national_holiday', 'school_holiday', 'special_event'])->default('regular');
            $table->boolean('is_working_day')->default(true);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_calendar_days');
    }
};
