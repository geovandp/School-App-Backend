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
        Schema::create('employee_attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();
            $table->enum('request_type', [
                'izin',
                'sakit',
                'cuti',
                'datang_terlambat',
                'keluar_sementara',
                'pulang_awal',
                'dinas_luar',
            ]);
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->text('reason');
            $table->string('attachment_path', 255)->nullable();
            $table->enum('request_status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamp('request_at')->useCurrent();
            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'request_status'], 'emp_att_req_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_requests');
    }
};
