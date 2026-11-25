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
        Schema::create('employee_attendance_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')
                ->constrained('employee_attendance_sessions', indexName: 'eae_session_fk')
                ->cascadeOnDelete();
            $table->foreignId('attendance_request_id')
                ->nullable()
                ->constrained('employee_attendance_requests', indexName: 'eae_request_fk')
                ->nullOnDelete();
            $table->enum('event_type', [
                'check_in',
                'temporary_check_out',
                'return_check_in',
                'final_check_out',
            ]);
            $table->timestamp('event_at');
            $table->enum('event_source', ['web', 'mobile', 'biometric', 'manual_adjustment', 'system'])->default('web');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->decimal('location_accuracy', 8, 2)->nullable();
            $table->string('device_identifier', 255)->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('discrepancy_id')->nullable();
            $table->timestamps();

            $table->index(['attendance_session_id', 'event_type', 'event_at'], 'emp_att_events_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_events');
    }
};
