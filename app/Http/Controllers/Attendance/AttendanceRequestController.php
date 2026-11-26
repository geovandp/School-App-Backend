<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use App\Models\EmployeeAttendanceRequest;
use App\Services\Attendance\AttendanceRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AttendanceRequestController extends Controller
{
    protected AttendanceRequestService $requestService;

    public function __construct(AttendanceRequestService $requestService)
    {
        $this->requestService = $requestService;
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
     * GET /api/v1/attendance/requests
     */
    public function index(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $query = EmployeeAttendanceRequest::where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('request_type')) {
            $query->where('request_type', $request->query('request_type'));
        }
        if ($request->filled('request_status')) {
            $query->where('request_status', $request->query('request_status'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate($request->query('per_page', 15)),
        ]);
    }

    /**
     * POST /api/v1/attendance/requests
     */
    public function store(Request $request): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $validated = $request->validate([
            'request_type' => ['required', 'in:izin,sakit,cuti,datang_terlambat,keluar_sementara,pulang_awal,dinas_luar'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        try {
            $attRequest = $this->requestService->createRequest(
                $employee,
                $validated,
                $request->file('attachment')
            );

            return response()->json([
                'success' => true,
                'message' => 'Permohonan kehadiran berhasil diajukan dan menunggu persetujuan.',
                'data' => $attRequest,
            ], 201);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * GET /api/v1/attendance/requests/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $attRequest = EmployeeAttendanceRequest::with(['approver', 'sessions'])
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $attRequest,
        ]);
    }

    /**
     * POST /api/v1/attendance/requests/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $employee = $this->getEmployee($request);

        $attRequest = EmployeeAttendanceRequest::where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        try {
            $this->requestService->cancelRequest($attRequest, $employee);

            return response()->json([
                'success' => true,
                'message' => 'Permohonan kehadiran berhasil dibatalkan.',
                'data' => $attRequest,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
