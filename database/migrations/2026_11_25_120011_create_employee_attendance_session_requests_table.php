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
        Schema::create('employee_attendance_session_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')
                ->constrained('employee_attendance_sessions', indexName: 'easr_session_fk')
                ->cascadeOnDelete();
            $table->foreignId('attendance_request_id')
                ->constrained('employee_attendance_requests', indexName: 'easr_request_fk')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'attendance_request_id'], 'att_sess_req_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_attendance_session_requests');
    }
};
