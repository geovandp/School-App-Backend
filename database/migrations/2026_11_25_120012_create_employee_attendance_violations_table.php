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
        Schema::create('employee_attendance_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')
                ->constrained('employee_attendance_sessions', indexName: 'eav_session_fk')
                ->cascadeOnDelete();
            $table->enum('violation_type', [
                'late',
                'early_leave',
                'unauthorized_temporary_out',
                'unauthorized_absence',
                'missing_check_out',
            ]);
            $table->timestamp('detected_at');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->text('description')->nullable();
            $table->string('evidence_path', 255)->nullable();
            $table->enum('review_status', ['pending', 'confirmed', 'excused', 'dismissed'])->default('pending');
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['attendance_session_id', 'violation_type', 'review_status'], 'emp_att_viol_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_violations');
    }
};
