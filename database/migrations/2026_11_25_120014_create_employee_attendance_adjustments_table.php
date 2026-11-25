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
        Schema::create('employee_attendance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')
                ->constrained('employee_attendance_sessions', indexName: 'eaa_session_fk')
                ->cascadeOnDelete();
            $table->foreignId('original_event_id')
                ->nullable()
                ->constrained('employee_attendance_events')
                ->nullOnDelete();
            $table->foreignId('created_event_id')
                ->nullable()
                ->constrained('employee_attendance_events')
                ->nullOnDelete();
            $table->enum('adjustment_type', [
                'add_missing_event',
                'correct_event',
                'cancel_invalid_event',
                'status_override',
            ]);
            $table->string('corrected_event_type', 50)->nullable();
            $table->timestamp('corrected_event_at')->nullable();
            $table->text('reason');
            $table->string('evidence_path', 255)->nullable();
            $table->foreignId('requested_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['attendance_session_id', 'approval_status'], 'emp_att_adj_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_adjustments');
    }
};
