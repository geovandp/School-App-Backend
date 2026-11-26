<?php

namespace App\Services\Attendance;

use App\Models\EmployeeAttendanceSession;
use App\Models\EmployeeAttendanceStatusHistory;
use App\Models\EmployeeAttendanceViolation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceEvaluationService
{
    /**
     * Evaluasi dan finalisasi seluruh sesi presensi pada tanggal tertentu.
     */
    public function finalizeDateAttendance(string $date = null): array
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();
        $now = Carbon::now();

        $sessions = EmployeeAttendanceSession::with(['employee', 'events', 'requests' => function ($q) {
            $q->where('request_status', 'approved');
        }])
            ->whereDate('attendance_date', $targetDate)
            ->whereNull('finalized_at')
            ->get();

        $recap = [
            'total_evaluated' => 0,
            'alpha' => 0,
            'tap' => 0,
            'tapt' => 0,
            'present' => 0,
        ];

        foreach ($sessions as $session) {
            DB::transaction(function () use ($session, $now, &$recap) {
                $hasCheckIn = $session->first_check_in_at !== null;
                $hasCheckOut = $session->last_check_out_at !== null;
                $oldStatus = $session->attendance_status;
                $newStatus = $oldStatus;

                // Cek apakah ada request izin/sakit/cuti yang disetujui untuk sesi ini
                $approvedRequest = $session->requests->first();

                if (!$hasCheckIn) {
                    if (!$approvedRequest) {
                        // Alpha (A)
                        $newStatus = EmployeeAttendanceSession::STATUS_ALPHA;
                        $recap['alpha']++;

                        // Catat pelanggaran alpha
                        EmployeeAttendanceViolation::firstOrCreate([
                            'attendance_session_id' => $session->id,
                            'violation_type' => EmployeeAttendanceViolation::TYPE_UNAUTHORIZED_ABSENCE,
                        ], [
                            'detected_at' => $now,
                            'description' => 'Tidak hadir dan tidak melakukan presensi masuk tanpa keterangan sah.',
                            'review_status' => EmployeeAttendanceViolation::STATUS_PENDING,
                        ]);
                    }
                } elseif (!$hasCheckOut) {
                    // Check-in ada, tapi tidak melakukan check-out
                    if ($session->late_minutes > 0) {
                        $newStatus = EmployeeAttendanceSession::STATUS_TAPT; // Tidak Absen Pulang Telat
                        $recap['tapt']++;
                    } else {
                        $newStatus = EmployeeAttendanceSession::STATUS_TAP; // Tidak Absen Pulang
                        $recap['tap']++;
                    }

                    // Catat pelanggaran tidak absen pulang
                    EmployeeAttendanceViolation::firstOrCreate([
                        'attendance_session_id' => $session->id,
                        'violation_type' => EmployeeAttendanceViolation::TYPE_MISSING_CHECK_OUT,
                    ], [
                        'detected_at' => $now,
                        'description' => 'Melakukan presensi masuk namun tidak melakukan presensi pulang.',
                        'review_status' => EmployeeAttendanceViolation::STATUS_PENDING,
                    ]);
                } else {
                    $recap['present']++;
                }

                $session->update([
                    'session_state' => EmployeeAttendanceSession::STATE_CLOSED,
                    'attendance_status' => $newStatus,
                    'finalized_at' => $now,
                ]);

                if ($newStatus !== $oldStatus) {
                    EmployeeAttendanceStatusHistory::create([
                        'attendance_session_id' => $session->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'reason' => "Finalisasi otomatis status kehadiran hari kerja ({$newStatus}).",
                        'changed_at' => $now,
                    ]);
                }

                $recap['total_evaluated']++;
            });
        }

        return $recap;
    }
}
