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
        Schema::create('employee_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();
            $table->foreignId('employee_work_schedule_id')
                ->nullable()
                ->constrained('employee_work_schedules', indexName: 'eas_work_sched_fk')
                ->nullOnDelete();
            $table->foreignId('school_calendar_day_id')
                ->nullable()
                ->constrained('school_calendar_days', indexName: 'eas_school_cal_fk')
                ->nullOnDelete();
            $table->date('attendance_date');
            $table->enum('session_state', ['open', 'closed', 'cancelled'])->default('open');
            $table->enum('attendance_status', [
                'H',    // Hadir
                'T',    // Terlambat
                'A',    // Alpha
                'TAP',  // Tidak Absen Pulang
                'TAPT', // Tidak Absen Pulang Telat
                'IP',   // Izin Pulang
                'PC',   // Pulang Cepat
                'I',    // Izin
                'S',    // Sakit
                'DL',   // Dinas Luar
                'CT',   // Cuti
            ])->default('A');
            $table->time('snapshot_start_time')->nullable()->comment('Snapshot jam masuk kerja');
            $table->time('snapshot_end_time')->nullable()->comment('Snapshot jam pulang kerja');
            $table->timestamp('first_check_in_at')->nullable();
            $table->timestamp('last_check_out_at')->nullable();
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('total_work_minutes')->default(0);
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'attendance_date'], 'emp_att_session_unique');
            $table->index(['attendance_date', 'attendance_status'], 'att_date_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_sessions');
    }
};
