<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendanceAdjustment;
use App\Services\Attendance\AttendanceAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AttendanceAdjustmentController extends Controller
{
    protected AttendanceAdjustmentService $adjustmentService;

    public function __construct(AttendanceAdjustmentService $adjustmentService)
    {
        $this->adjustmentService = $adjustmentService;
    }

    protected function getEmployee(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            abort(403, 'Akun Anda tidak terdaftar sebagai pegawai/guru.');
        }
        return $employee;
    }

    /**
     * GET /api/v1/attendance/adjustments
     */
    public function index(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $adjustments = EmployeeAttendanceAdjustment::where('requested_by', $employee->user_id)
            ->with(['session', 'originalEvent', 'createdEvent', 'approver'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $adjustments,
        ]);
    }

    /**
     * POST /api/v1/attendance/adjustments
     */
    public function store(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $validated = $request->validate([
            'attendance_session_id' => ['required', 'exists:employee_attendance_sessions,id'],
            'original_event_id' => ['nullable', 'exists:employee_attendance_events,id'],
            'adjustment_type' => ['required', 'in:add_missing_event,correct_event,cancel_invalid_event,status_override'],
            'corrected_event_type' => ['nullable', 'in:check_in,temporary_check_out,return_check_in,final_check_out'],
            'corrected_event_at' => ['nullable', 'date_format:Y-m-d H:i:s'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'evidence' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        try {
            $adjustment = $this->adjustmentService->createAdjustment(
                $employee,
                $validated,
                $request->file('evidence')
            );

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan koreksi presensi berhasil diajukan dan menunggu persetujuan admin.',
                'data' => $adjustment,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/v1/attendance/adjustments/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $adjustment = EmployeeAttendanceAdjustment::with(['session', 'originalEvent', 'createdEvent', 'approver'])
            ->where('id', $id)
            ->where('requested_by', $employee->user_id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $adjustment,
        ]);
    }
}
