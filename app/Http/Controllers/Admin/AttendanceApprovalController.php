<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendanceRequest;
use App\Services\Attendance\AttendanceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AttendanceApprovalController extends Controller
{
    protected AttendanceRequestService $requestService;

    public function __construct(AttendanceRequestService $requestService)
    {
        $this->requestService = $requestService;
    }

    /**
     * GET /api/v1/admin/attendance-approvals
     */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', EmployeeAttendanceRequest::STATUS_PENDING);

        $query = EmployeeAttendanceRequest::with(['employee.user.profile'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('request_status', $status);
        }
        if ($request->filled('request_type')) {
            $query->where('request_type', $request->query('request_type'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->query('per_page', 20)),
        ]);
    }

    /**
     * GET /api/v1/admin/attendance-approvals/{id}
     */
    public function show(int $id): JsonResponse
    {
        $attRequest = EmployeeAttendanceRequest::with([
            'employee.user.profile',
            'employee.employeeWorkSchedules.workSchedule',
            'approver',
            'sessions',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $attRequest,
        ]);
    }

    /**
     * POST /api/v1/admin/attendance-approvals/{id}/approve
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $attRequest = EmployeeAttendanceRequest::findOrFail($id);

        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $result = $this->requestService->approveRequest(
                $attRequest,
                $request->user(),
                $validated['approval_note'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Permohonan kehadiran berhasil disetujui.',
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
     * POST /api/v1/admin/attendance-approvals/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $attRequest = EmployeeAttendanceRequest::findOrFail($id);

        $validated = $request->validate([
            'approval_note' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $result = $this->requestService->rejectRequest(
                $attRequest,
                $request->user(),
                $validated['approval_note']
            );

            return response()->json([
                'success' => true,
                'message' => 'Permohonan kehadiran berhasil ditolak.',
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
