<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendanceAdjustment;
use App\Services\Attendance\AttendanceAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AttendanceAdjustmentAdminController extends Controller
{
    protected AttendanceAdjustmentService $adjustmentService;

    public function __construct(AttendanceAdjustmentService $adjustmentService)
    {
        $this->adjustmentService = $adjustmentService;
    }

    /**
     * GET /api/v1/admin/attendance-adjustments
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', EmployeeAttendanceAdjustment::STATUS_PENDING);

        $query = EmployeeAttendanceAdjustment::with([
            'session.employee.user.profile',
            'originalEvent',
            'createdEvent',
            'requester.profile',
            'approver',
        ])->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('approval_status', $status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->query('per_page', 20)),
        ]);
    }

    /**
     * GET /api/v1/admin/attendance-adjustments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $adjustment = EmployeeAttendanceAdjustment::with([
            'session.employee.user.profile',
            'session.events',
            'originalEvent',
            'createdEvent',
            'requester.profile',
            'approver',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $adjustment,
        ]);
    }

    /**
     * POST /api/v1/admin/attendance-adjustments/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $adjustment = EmployeeAttendanceAdjustment::findOrFail($id);

        try {
            $result = $this->adjustmentService->approveAdjustment($adjustment, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Koreksi presensi berhasil disetujui.',
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * POST /api/v1/admin/attendance-adjustments/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $adjustment = EmployeeAttendanceAdjustment::findOrFail($id);

        try {
            $result = $this->adjustmentService->rejectAdjustment($adjustment, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Koreksi presensi berhasil ditolak.',
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
