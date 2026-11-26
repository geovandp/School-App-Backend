<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendanceSession;
use App\Models\EmployeeAttendanceViolation;
use App\Services\Attendance\AttendanceEvaluationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AttendanceAdminController extends Controller
{
    protected AttendanceEvaluationService $evaluationService;

    public function __construct(AttendanceEvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    /**
     * GET /api/v1/admin/attendance/sessions
     */
    public function sessions(Request $request): JsonResponse
    {
        $query = EmployeeAttendanceSession::with(['employee.user.profile', 'employeeWorkSchedule.workSchedule'])
            ->orderBy('attendance_date', 'desc');

        if ($request->filled('date')) {
            $query->where('attendance_date', $request->query('date'));
        }
        if ($request->filled('status')) {
            $query->where('attendance_status', $request->query('status'));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('employee.user.profile', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->query('per_page', 20)),
        ]);
    }

    /**
     * GET /api/v1/admin/attendance/sessions/{id}
     */
    public function sessionDetail(int $id): JsonResponse
    {
        $session = EmployeeAttendanceSession::with([
            'employee.user.profile',
            'employeeWorkSchedule.workSchedule',
            'events',
            'requests',
            'violations.reviewer',
            'statusHistories.changer',
            'adjustments',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }

    /**
     * GET /api/v1/admin/attendance/recap
     */
    public function recap(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::today()->toDateString());

        $sessions = EmployeeAttendanceSession::with(['employee.user.profile'])
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        $grouped = $sessions->groupBy('employee_id')->map(function ($empSessions) {
            $first = $empSessions->first();
            return [
                'employee_id' => $first->employee_id,
                'employee_number' => $first->employee?->employee_number,
                'name' => $first->employee?->user?->profile?->full_name,
                'total_days' => $empSessions->count(),
                'hadir' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_HADIR)->count(),
                'terlambat' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_TERLAMBAT)->count(),
                'alpha' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_ALPHA)->count(),
                'tap' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_TAP)->count(),
                'tapt' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_TAPT)->count(),
                'izin' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_IZIN)->count(),
                'sakit' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_SAKIT)->count(),
                'cuti' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_CUTI)->count(),
                'dinas_luar' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_DINAS_LUAR)->count(),
                'izin_pulang' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_IZIN_PULANG)->count(),
                'pulang_cepat' => $empSessions->where('attendance_status', EmployeeAttendanceSession::STATUS_PULANG_CEPAT)->count(),
                'total_late_minutes' => $empSessions->sum('late_minutes'),
                'total_work_minutes' => $empSessions->sum('total_work_minutes'),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'data' => $grouped,
        ]);
    }

    /**
     * GET /api/v1/admin/attendance/violations
     */
    public function violations(Request $request): JsonResponse
    {
        $query = EmployeeAttendanceViolation::with(['session.employee.user.profile', 'reviewer'])
            ->orderBy('detected_at', 'desc');

        if ($request->filled('violation_type')) {
            $query->where('violation_type', $request->query('violation_type'));
        }
        if ($request->filled('review_status')) {
            $query->where('review_status', $request->query('review_status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->query('per_page', 20)),
        ]);
    }

    /**
     * POST /api/v1/admin/attendance/violations/{id}/review
     */
    public function reviewViolation(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'review_status' => ['required', 'in:confirmed,excused,dismissed'],
            'resolution_note' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $violation = EmployeeAttendanceViolation::findOrFail($id);

        $violation->update([
            'review_status' => $validated['review_status'],
            'resolution_note' => $validated['resolution_note'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pelanggaran presensi berhasil diperbarui.',
            'data' => $violation,
        ]);
    }

    /**
     * POST /api/v1/admin/attendance/finalize
     */
    public function finalize(Request $request): JsonResponse
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        try {
            $result = $this->evaluationService->finalizeDateAttendance($date);

            return response()->json([
                'success' => true,
                'message' => "Presensi tanggal {$date} berhasil dievaluasi dan difinalisasi.",
                'summary' => $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
