<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\EmployeeAttendanceAdjustment;
use App\Models\EmployeeAttendanceEvent;
use App\Models\EmployeeAttendanceSession;
use App\Models\EmployeeAttendanceStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceAdjustmentService
{
    /**
     * Buat permohonan koreksi/adjustment presensi.
     */
    public function createAdjustment(Employee $employee, array $data, ?UploadedFile $evidence = null): EmployeeAttendanceAdjustment
    {
        $session = EmployeeAttendanceSession::findOrFail($data['attendance_session_id']);
        if ($session->employee_id !== $employee->id) {
            throw new InvalidArgumentException('Anda tidak berhak mengajukan koreksi untuk sesi ini.');
        }

        $evidencePath = null;
        if ($evidence) {
            $evidencePath = $evidence->store('attendance/adjustments', 'public');
        }

        return EmployeeAttendanceAdjustment::create([
            'attendance_session_id' => $session->id,
            'original_event_id' => $data['original_event_id'] ?? null,
            'adjustment_type' => $data['adjustment_type'],
            'corrected_event_type' => $data['corrected_event_type'] ?? null,
            'corrected_event_at' => isset($data['corrected_event_at']) ? Carbon::parse($data['corrected_event_at']) : null,
            'reason' => $data['reason'],
            'evidence_path' => $evidencePath,
            'requested_by' => $employee->user_id,
            'approval_status' => EmployeeAttendanceAdjustment::STATUS_PENDING,
        ]);
    }

    /**
     * Setujui permohonan koreksi presensi.
     */
    public function approveAdjustment(EmployeeAttendanceAdjustment $adjustment, User $approver): EmployeeAttendanceAdjustment
    {
        if ($adjustment->approval_status !== EmployeeAttendanceAdjustment::STATUS_PENDING) {
            throw new InvalidArgumentException('Hanya pengajuan berstatus pending yang dapat disetujui.');
        }

        return DB::transaction(function () use ($adjustment, $approver) {
            $now = Carbon::now();
            $session = $adjustment->session;
            $createdEvent = null;

            if (in_array($adjustment->adjustment_type, [
                EmployeeAttendanceAdjustment::TYPE_ADD_MISSING_EVENT,
                EmployeeAttendanceAdjustment::TYPE_CORRECT_EVENT
            ])) {
                // Buat event baru hasil koreksi
                $createdEvent = EmployeeAttendanceEvent::create([
                    'attendance_session_id' => $session->id,
                    'event_type' => $adjustment->corrected_event_type,
                    'event_at' => $adjustment->corrected_event_at ?: $now,
                    'event_source' => EmployeeAttendanceEvent::SOURCE_MANUAL_ADJUSTMENT,
                    'reason' => "Koreksi presensi disetujui: {$adjustment->reason}",
                ]);
            }

            // Update sesi: recalculate first_check_in_at dan last_check_out_at
            $firstCheckIn = $session->events()->where('event_type', EmployeeAttendanceEvent::TYPE_CHECK_IN)->min('event_at');
            $lastCheckOut = $session->events()->where('event_type', EmployeeAttendanceEvent::TYPE_FINAL_CHECK_OUT)->max('event_at');

            $oldStatus = $session->attendance_status;
            $newStatus = $oldStatus;

            if ($firstCheckIn && $lastCheckOut) {
                $newStatus = EmployeeAttendanceSession::STATUS_HADIR;
                $startTime = Carbon::parse($session->attendance_date->toDateString() . ' ' . $session->snapshot_start_time);
                if (Carbon::parse($firstCheckIn)->greaterThan($startTime->addMinutes(15))) {
                    $newStatus = EmployeeAttendanceSession::STATUS_TERLAMBAT;
                }
            }

            $session->update([
                'first_check_in_at' => $firstCheckIn,
                'last_check_out_at' => $lastCheckOut,
                'attendance_status' => $newStatus,
                'session_state' => $lastCheckOut ? EmployeeAttendanceSession::STATE_CLOSED : $session->session_state,
            ]);

            $adjustment->update([
                'created_event_id' => $createdEvent?->id,
                'approved_by' => $approver->id,
                'approval_status' => EmployeeAttendanceAdjustment::STATUS_APPROVED,
                'approved_at' => $now,
            ]);

            if ($newStatus !== $oldStatus) {
                EmployeeAttendanceStatusHistory::create([
                    'attendance_session_id' => $session->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => "Status disesuaikan via koreksi presensi (Adjustment #{$adjustment->id}).",
                    'changed_by' => $approver->id,
                    'changed_at' => $now,
                ]);
            }

            return $adjustment;
        });
    }

    /**
     * Tolak permohonan koreksi presensi.
     */
    public function rejectAdjustment(EmployeeAttendanceAdjustment $adjustment, User $approver): EmployeeAttendanceAdjustment
    {
        if ($adjustment->approval_status !== EmployeeAttendanceAdjustment::STATUS_PENDING) {
            throw new InvalidArgumentException('Hanya pengajuan berstatus pending yang dapat ditolak.');
        }

        $adjustment->update([
            'approved_by' => $approver->id,
            'approval_status' => EmployeeAttendanceAdjustment::STATUS_REJECTED,
            'approved_at' => Carbon::now(),
        ]);

        return $adjustment;
    }
}
