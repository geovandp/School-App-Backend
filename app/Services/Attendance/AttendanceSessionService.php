<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\EmployeeAttendanceSession;
use App\Models\EmployeeWorkSchedule;
use App\Models\SchoolCalendarDay;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceSessionService
{
    /**
     * Dapatkan sesi presensi hari ini atau buat baru jika belum ada (On-demand generation).
     */
    public function getOrCreateTodaySession(Employee $employee, Carbon $now = null): EmployeeAttendanceSession
    {
        $now = $now ?: Carbon::now();
        $dateStr = $now->toDateString();

        return DB::transaction(function () use ($employee, $now, $dateStr) {
            $existingSession = EmployeeAttendanceSession::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $dateStr)
                ->lockForUpdate()
                ->first();

            if ($existingSession) {
                return $existingSession;
            }

            // Cek kalender sekolah
            $calendarDay = SchoolCalendarDay::whereDate('calendar_date', $dateStr)->first();

            // Cek jadwal kerja aktif pegawai
            $empSchedule = $employee->getActiveWorkSchedule($dateStr);
            if (!$empSchedule) {
                throw new InvalidArgumentException('Pegawai belum memiliki jadwal kerja aktif untuk tanggal ini.');
            }

            $workSchedule = $empSchedule->workSchedule;
            if (!$workSchedule || !$workSchedule->is_active) {
                throw new InvalidArgumentException('Jadwal kerja pegawai sedang non-aktif.');
            }

            // Validasi apakah hari ini adalah hari kerja sesuai jadwal (1=Mon ... 7=Sun)
            $dayOfWeek = $now->dayOfWeekIso;
            $isScheduledDay = $workSchedule->days()->where('day_of_week', $dayOfWeek)->exists();

            if (!$isScheduledDay && (!$calendarDay || !$calendarDay->is_working_day)) {
                throw new InvalidArgumentException('Hari ini bukan hari kerja sesuai jadwal.');
            }

            return EmployeeAttendanceSession::create([
                'employee_id' => $employee->id,
                'employee_work_schedule_id' => $empSchedule->id,
                'school_calendar_day_id' => $calendarDay?->id,
                'attendance_date' => $dateStr,
                'session_state' => EmployeeAttendanceSession::STATE_OPEN,
                'attendance_status' => EmployeeAttendanceSession::STATUS_ALPHA, // Default status awal
                'snapshot_start_time' => $workSchedule->start_time,
                'snapshot_end_time' => $workSchedule->end_time,
                'late_minutes' => 0,
                'early_leave_minutes' => 0,
                'total_work_minutes' => 0,
            ]);
        });
    }

    /**
     * Generate sesi absensi massal untuk scheduler harian.
     */
    public function generateSessionsForDate(Carbon $date): int
    {
        $dateStr = $date->toDateString();
        $dayOfWeek = $date->dayOfWeekIso;

        $calendarDay = SchoolCalendarDay::where('calendar_date', $dateStr)->first();
        if ($calendarDay && !$calendarDay->is_working_day) {
            return 0; // Libur sekolah
        }

        $activeEmployees = Employee::with(['employeeWorkSchedules' => function ($q) use ($dateStr) {
            $q->where('effective_from', '<=', $dateStr)
                ->where(function ($sub) use ($dateStr) {
                    $sub->whereNull('effective_until')->orWhere('effective_until', '>=', $dateStr);
                })
                ->latest('effective_from');
        }, 'employeeWorkSchedules.workSchedule.days'])->get();

        $count = 0;

        foreach ($activeEmployees as $employee) {
            $empSchedule = $employee->employeeWorkSchedules->first();
            if (!$empSchedule || !$empSchedule->workSchedule || !$empSchedule->workSchedule->is_active) {
                continue;
            }

            $isScheduled = $empSchedule->workSchedule->days->contains('day_of_week', $dayOfWeek);
            if (!$isScheduled) {
                continue;
            }

            $exists = EmployeeAttendanceSession::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $dateStr)
                ->exists();

            if (!$exists) {
                EmployeeAttendanceSession::create([
                    'employee_id' => $employee->id,
                    'employee_work_schedule_id' => $empSchedule->id,
                    'school_calendar_day_id' => $calendarDay?->id,
                    'attendance_date' => $dateStr,
                    'session_state' => EmployeeAttendanceSession::STATE_OPEN,
                    'attendance_status' => EmployeeAttendanceSession::STATUS_ALPHA,
                    'snapshot_start_time' => $empSchedule->workSchedule->start_time,
                    'snapshot_end_time' => $empSchedule->workSchedule->end_time,
                    'late_minutes' => 0,
                    'early_leave_minutes' => 0,
                    'total_work_minutes' => 0,
                ]);
                $count++;
            }
        }

        return $count;
    }
}
