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
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_level_id')->constrained('grade_levels')->restrictOnDelete();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->foreignId('major_id')->constrained('majors')->restrictOnDelete();
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->unique([
                'name',
                'grade_level_id',
                'academic_year_id',
                'major_id',
            ], 'classrooms_unique_class');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
