<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\EmployeeAttendanceEvent;
use App\Models\EmployeeAttendanceRequest;
use App\Models\EmployeeAttendanceSession;
use App\Models\EmployeeAttendanceStatusHistory;
use App\Models\EmployeeAttendanceViolation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceEventService
{
    protected AttendanceSessionService $sessionService;

    public function __construct(AttendanceSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Validasi radius geofencing 1 km.
     */
    protected function validateLocation(float $latitude, float $longitude): void
    {
        if (!EmployeeAttendanceEvent::isWithinAllowedRadius($latitude, $longitude)) {
            $maxKm = config('attendance.max_radius_meters', 1000) / 1000;
            throw new InvalidArgumentException("Lokasi Anda berada di luar radius presensi yang diizinkan (maksimal {$maxKm} km dari sekolah).");
        }
    }

    /**
     * Record Check-In
     */
    public function recordCheckIn(Employee $employee, array $data): EmployeeAttendanceEvent
    {
        $this->validateLocation((float) $data['latitude'], (float) $data['longitude']);

        $now = Carbon::now();

        return DB::transaction(function () use ($employee, $data, $now) {
            $session = $this->sessionService->getOrCreateTodaySession($employee, $now);

            // Cek apakah sudah check-in
            $hasCheckIn = $session->events()->where('event_type', EmployeeAttendanceEvent::TYPE_CHECK_IN)->exists();
            if ($hasCheckIn) {
                throw new InvalidArgumentException('Anda sudah melakukan presensi masuk (check-in) untuk hari ini.');
            }

            // Simpan event
            $event = EmployeeAttendanceEvent::create([
                'attendance_session_id' => $session->id,
                'event_type' => EmployeeAttendanceEvent::TYPE_CHECK_IN,
                'event_at' => $now,
                'event_source' => $data['event_source'] ?? EmployeeAttendanceEvent::SOURCE_WEB,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'location_accuracy' => $data['location_accuracy'] ?? null,
                'device_identifier' => $data['device_identifier'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]);

            // Hitung keterlambatan berdasarkan snapshot jadwal
            $startTime = Carbon::parse($session->attendance_date->toDateString() . ' ' . $session->snapshot_start_time);
            $lateToleranceMinutes = $session->employeeWorkSchedule?->workSchedule?->late_tolerance_minutes ?? 15;
            $allowedTime = $startTime->copy()->addMinutes($lateToleranceMinutes);

            $oldStatus = $session->attendance_status;
            $newStatus = EmployeeAttendanceSession::STATUS_HADIR;
            $lateMinutes = 0;

            if ($now->greaterThan($allowedTime)) {
                $newStatus = EmployeeAttendanceSession::STATUS_TERLAMBAT;
                $lateMinutes = abs((int) $now->diffInMinutes($startTime));

                // Catat pelanggaran terlambat
                EmployeeAttendanceViolation::create([
                    'attendance_session_id' => $session->id,
                    'violation_type' => EmployeeAttendanceViolation::TYPE_LATE,
                    'detected_at' => $now,
                    'start_at' => $startTime,
                    'end_at' => $now,
                    'duration_minutes' => $lateMinutes,
                    'description' => "Terlambat {$lateMinutes} menit dari jadwal {$session->snapshot_start_time}.",
                    'review_status' => EmployeeAttendanceViolation::STATUS_PENDING,
                ]);
            }

            // Update session
            $session->update([
                'first_check_in_at' => $now,
                'late_minutes' => $lateMinutes,
                'attendance_status' => $newStatus,
            ]);

            // Catat riwayat status
            EmployeeAttendanceStatusHistory::create([
                'attendance_session_id' => $session->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $newStatus === EmployeeAttendanceSession::STATUS_TERLAMBAT
                    ? "Check-in terlambat {$lateMinutes} menit."
                    : 'Check-in tepat waktu.',
                'changed_by' => $employee->user_id,
                'changed_at' => $now,
            ]);

            return $event;
        });
    }

    /**
     * Record Temporary Check-Out
     */
    public function recordTemporaryCheckOut(Employee $employee, array $data): EmployeeAttendanceEvent
    {
        $this->validateLocation((float) $data['latitude'], (float) $data['longitude']);

        $now = Carbon::now();

        return DB::transaction(function () use ($employee, $data, $now) {
            $session = $this->sessionService->getOrCreateTodaySession($employee, $now);

            $lastEvent = $session->events()->latest('id')->first();
            if (!$lastEvent || $lastEvent->event_type === EmployeeAttendanceEvent::TYPE_FINAL_CHECK_OUT) {
                throw new InvalidArgumentException('Anda belum melakukan check-in atau sudah melakukan final check-out.');
            }

            if ($lastEvent->event_type === EmployeeAttendanceEvent::TYPE_TEMPORARY_CHECK_OUT) {
                throw new InvalidArgumentException('Anda sudah berstatus keluar sementara.');
            }

            // Cek apakah ada request izin keluar sementara yang disetujui
            $hasApprovedRequest = $session->requests()
                ->where('request_type', EmployeeAttendanceRequest::TYPE_KELUAR_SEMENTARA)
                ->where('request_status', EmployeeAttendanceRequest::STATUS_APPROVED)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->exists();

            if (!$hasApprovedRequest) {
                // Catat pelanggaran keluar sementara tanpa izin
                EmployeeAttendanceViolation::create([
                    'attendance_session_id' => $session->id,
                    'violation_type' => EmployeeAttendanceViolation::TYPE_UNAUTHORIZED_TEMPORARY_OUT,
                    'detected_at' => $now,
                    'start_at' => $now,
                    'description' => 'Keluar sementara tanpa permohonan/izin resmi yang disetujui: ' . ($data['reason'] ?? '-'),
                    'review_status' => EmployeeAttendanceViolation::STATUS_PENDING,
                ]);
            }

            return EmployeeAttendanceEvent::create([
                'attendance_session_id' => $session->id,
                'event_type' => EmployeeAttendanceEvent::TYPE_TEMPORARY_CHECK_OUT,
                'event_at' => $now,
                'event_source' => $data['event_source'] ?? EmployeeAttendanceEvent::SOURCE_WEB,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'location_accuracy' => $data['location_accuracy'] ?? null,
                'device_identifier' => $data['device_identifier'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]);
        });
    }

    /**
     * Record Return Check-In
     */
    public function recordReturnCheckIn(Employee $employee, array $data): EmployeeAttendanceEvent
    {
        $this->validateLocation((float) $data['latitude'], (float) $data['longitude']);

        $now = Carbon::now();

        return DB::transaction(function () use ($employee, $data, $now) {
            $session = $this->sessionService->getOrCreateTodaySession($employee, $now);

            $lastEvent = $session->events()->latest('id')->first();
            if (!$lastEvent || $lastEvent->event_type !== EmployeeAttendanceEvent::TYPE_TEMPORARY_CHECK_OUT) {
                throw new InvalidArgumentException('Anda tidak sedang dalam status keluar sementara.');
            }

            // Update durasi pelanggaran unauthorized out jika ada
            $violation = $session->violations()
                ->where('violation_type', EmployeeAttendanceViolation::TYPE_UNAUTHORIZED_TEMPORARY_OUT)
                ->whereNull('end_at')
                ->latest('detected_at')
                ->first();

            if ($violation) {
                $duration = (int) $now->diffInMinutes($violation->start_at);
                $violation->update([
                    'end_at' => $now,
                    'duration_minutes' => $duration,
                ]);
            }

            return EmployeeAttendanceEvent::create([
                'attendance_session_id' => $session->id,
                'event_type' => EmployeeAttendanceEvent::TYPE_RETURN_CHECK_IN,
                'event_at' => $now,
                'event_source' => $data['event_source'] ?? EmployeeAttendanceEvent::SOURCE_WEB,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'location_accuracy' => $data['location_accuracy'] ?? null,
                'device_identifier' => $data['device_identifier'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]);
        });
    }

    /**
     * Record Final Check-Out
     */
    public function recordFinalCheckOut(Employee $employee, array $data): EmployeeAttendanceEvent
    {
        $this->validateLocation((float) $data['latitude'], (float) $data['longitude']);

        $now = Carbon::now();

        return DB::transaction(function () use ($employee, $data, $now) {
            $session = $this->sessionService->getOrCreateTodaySession($employee, $now);

            $lastEvent = $session->events()->latest('id')->first();
            if (!$lastEvent) {
                throw new InvalidArgumentException('Anda belum melakukan presensi masuk (check-in).');
            }

            if ($lastEvent->event_type === EmployeeAttendanceEvent::TYPE_TEMPORARY_CHECK_OUT) {
                throw new InvalidArgumentException('Silakan lakukan presensi kembali (return check-in) terlebih dahulu sebelum pulang.');
            }

            $event = EmployeeAttendanceEvent::create([
                'attendance_session_id' => $session->id,
                'event_type' => EmployeeAttendanceEvent::TYPE_FINAL_CHECK_OUT,
                'event_at' => $now,
                'event_source' => $data['event_source'] ?? EmployeeAttendanceEvent::SOURCE_WEB,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'location_accuracy' => $data['location_accuracy'] ?? null,
                'device_identifier' => $data['device_identifier'] ?? null,
                'reason' => $data['reason'] ?? null,
            ]);

            // Hitung jam pulang & early leave
            $endTime = Carbon::parse($session->attendance_date->toDateString() . ' ' . $session->snapshot_end_time);
            $earlyLeaveMinutes = 0;
            $oldStatus = $session->attendance_status;
            $newStatus = $oldStatus;

            if ($now->lessThan($endTime)) {
                $earlyLeaveMinutes = abs((int) $endTime->diffInMinutes($now));

                // Cek apakah ada izin pulang lebih awal
                $hasEarlyLeaveApproved = $session->requests()
                    ->whereIn('request_type', [EmployeeAttendanceRequest::TYPE_PULANG_AWAL, EmployeeAttendanceRequest::TYPE_IZIN])
                    ->where('request_status', EmployeeAttendanceRequest::STATUS_APPROVED)
                    ->exists();

                if ($hasEarlyLeaveApproved) {
                    $newStatus = EmployeeAttendanceSession::STATUS_IZIN_PULANG; // IP
                } else {
                    $newStatus = EmployeeAttendanceSession::STATUS_PULANG_CEPAT; // PC
                    // Catat pelanggaran early leave
                    EmployeeAttendanceViolation::create([
                        'attendance_session_id' => $session->id,
                        'violation_type' => EmployeeAttendanceViolation::TYPE_EARLY_LEAVE,
                        'detected_at' => $now,
                        'start_at' => $now,
                        'end_at' => $endTime,
                        'duration_minutes' => $earlyLeaveMinutes,
                        'description' => "Pulang {$earlyLeaveMinutes} menit lebih awal sebelum jam pulang ({$session->snapshot_end_time}) tanpa izin resmi.",
                        'review_status' => EmployeeAttendanceViolation::STATUS_PENDING,
                    ]);
                }
            }

            // Total jam kerja (selisih check-in dan final check-out dikurangi waktu keluar sementara jika ada)
            $totalMinutes = abs((int) $now->diffInMinutes($session->first_check_in_at));

            $session->update([
                'last_check_out_at' => $now,
                'early_leave_minutes' => $earlyLeaveMinutes,
                'total_work_minutes' => max(0, $totalMinutes),
                'session_state' => EmployeeAttendanceSession::STATE_CLOSED,
                'attendance_status' => $newStatus,
            ]);

            if ($newStatus !== $oldStatus) {
                EmployeeAttendanceStatusHistory::create([
                    'attendance_session_id' => $session->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $newStatus === EmployeeAttendanceSession::STATUS_IZIN_PULANG
                        ? 'Pulang lebih awal dengan izin resmi.'
                        : "Pulang cepat {$earlyLeaveMinutes} menit tanpa izin.",
                    'changed_by' => $employee->user_id,
                    'changed_at' => $now,
                ]);
            }

            return $event;
        });
    }
}
