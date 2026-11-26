<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendanceSession;
use App\Services\Attendance\AttendanceEventService;
use App\Services\Attendance\AttendanceSessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AttendanceController extends Controller
{
    protected AttendanceSessionService $sessionService;
    protected AttendanceEventService $eventService;

    public function __construct(AttendanceSessionService $sessionService, AttendanceEventService $eventService)
    {
        $this->sessionService = $sessionService;
        $this->eventService = $eventService;
    }

    /**
     * Dapatkan instance Employee milik user yang sedang login.
     */
    protected function getEmployee(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            abort(403, 'Akun Anda tidak terdaftar sebagai pegawai/guru.');
        }

        return $employee;
    }

    /**
     * GET /api/v1/attendance/today
     */
    public function today(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);
        $today = Carbon::today();

        try {
            $session = $this->sessionService->getOrCreateTodaySession($employee, $today);
            $session->load(['events', 'employeeWorkSchedule.workSchedule', 'violations']);

            return response()->json([
                'success' => true,
                'data' => [
                    'session_id' => $session->id,
                    'attendance_date' => $session->attendance_date->toDateString(),
                    'session_state' => $session->session_state,
                    'attendance_status' => $session->attendance_status,
                    'schedule' => [
                        'name' => $session->employeeWorkSchedule?->workSchedule?->name,
                        'start_time' => $session->snapshot_start_time,
                        'end_time' => $session->snapshot_end_time,
                        'late_tolerance_minutes' => $session->employeeWorkSchedule?->workSchedule?->late_tolerance_minutes ?? 15,
                    ],
                    'first_check_in_at' => $session->first_check_in_at?->toDateTimeString(),
                    'last_check_out_at' => $session->last_check_out_at?->toDateTimeString(),
                    'late_minutes' => $session->late_minutes,
                    'early_leave_minutes' => $session->early_leave_minutes,
                    'total_work_minutes' => $session->total_work_minutes,
                    'events' => $session->events,
                    'violations' => $session->violations,
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/v1/attendance/check-in
     */
    public function checkIn(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['nullable', 'numeric'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $event = $this->eventService->recordCheckIn($employee, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Presensi masuk (check-in) berhasil dicatat.',
                'data' => $event,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/v1/attendance/temporary-check-out
     */
    public function temporaryCheckOut(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['nullable', 'numeric'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        try {
            $event = $this->eventService->recordTemporaryCheckOut($employee, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Presensi keluar sementara berhasil dicatat.',
                'data' => $event,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/v1/attendance/return-check-in
     */
    public function returnCheckIn(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['nullable', 'numeric'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $event = $this->eventService->recordReturnCheckIn($employee, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Presensi kembali (return check-in) berhasil dicatat.',
                'data' => $event,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/v1/attendance/final-check-out
     */
    public function finalCheckOut(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_accuracy' => ['nullable', 'numeric'],
            'device_identifier' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $event = $this->eventService->recordFinalCheckOut($employee, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Presensi pulang (final check-out) berhasil dicatat.',
                'data' => $event,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/v1/attendance/history
     */
    public function history(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $query = EmployeeAttendanceSession::with(['events'])
            ->where('employee_id', $employee->id)
            ->orderBy('attendance_date', 'desc');

        if ($request->filled('start_date')) {
            $query->where('attendance_date', '>=', $request->query('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->where('attendance_date', '<=', $request->query('end_date'));
        }
        if ($request->filled('status')) {
            $query->where('attendance_status', $request->query('status'));
        }

        $sessions = $query->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * GET /api/v1/attendance/sessions/{id}
     */
    public function sessionDetail(Request $request, int $id): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $session = EmployeeAttendanceSession::with(['events', 'requests', 'violations', 'statusHistories'])
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }

    /**
     * GET /api/v1/attendance/recap
     */
    public function personalRecap(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $month = $request->query('month', Carbon::now()->month);
        $year = $request->query('year', Carbon::now()->year);

        $sessions = EmployeeAttendanceSession::where('employee_id', $employee->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->get();

        $recap = [
            'month' => (int) $month,
            'year' => (int) $year,
            'total_days' => $sessions->count(),
            'hadir' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_HADIR)->count(),
            'terlambat' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_TERLAMBAT)->count(),
            'alpha' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_ALPHA)->count(),
            'tap' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_TAP)->count(),
            'tapt' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_TAPT)->count(),
            'izin' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_IZIN)->count(),
            'sakit' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_SAKIT)->count(),
            'cuti' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_CUTI)->count(),
            'dinas_luar' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_DINAS_LUAR)->count(),
            'izin_pulang' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_IZIN_PULANG)->count(),
            'pulang_cepat' => $sessions->where('attendance_status', EmployeeAttendanceSession::STATUS_PULANG_CEPAT)->count(),
            'total_late_minutes' => $sessions->sum('late_minutes'),
            'total_work_minutes' => $sessions->sum('total_work_minutes'),
        ];

        return response()->json([
            'success' => true,
            'data' => $recap,
        ]);
    }
}
