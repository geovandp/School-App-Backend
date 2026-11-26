<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\EmployeeAttendanceRequest;
use App\Models\EmployeeAttendanceSession;
use App\Models\EmployeeAttendanceStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AttendanceRequestService
{
    protected AttendanceSessionService $sessionService;

    public function __construct(AttendanceSessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
     * Buat permohonan kehadiran baru.
     */
    public function createRequest(Employee $employee, array $data, ?UploadedFile $attachment = null): EmployeeAttendanceRequest
    {
        $start = Carbon::parse($data['start_at']);
        $end = Carbon::parse($data['end_at']);

        if ($end->lessThan($start)) {
            throw new InvalidArgumentException('Waktu selesai tidak boleh lebih awal dari waktu mulai.');
        }

        // Cek overlap request aktif
        $overlap = EmployeeAttendanceRequest::where('employee_id', $employee->id)
            ->whereIn('request_status', [EmployeeAttendanceRequest::STATUS_PENDING, EmployeeAttendanceRequest::STATUS_APPROVED])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(function ($sub) use ($start, $end) {
                        $sub->where('start_at', '<=', $start)->where('end_at', '>=', $end);
                    });
            })
            ->exists();

        if ($overlap) {
            throw new InvalidArgumentException('Sudah ada permohonan kehadiran lain pada rentang waktu yang sama.');
        }

        $attachmentPath = null;
        if ($attachment) {
            $attachmentPath = $attachment->store('attendance/requests', 'public');
        }

        return EmployeeAttendanceRequest::create([
            'employee_id' => $employee->id,
            'request_type' => $data['request_type'],
            'start_at' => $start,
            'end_at' => $end,
            'reason' => $data['reason'],
            'attachment_path' => $attachmentPath,
            'request_status' => EmployeeAttendanceRequest::STATUS_PENDING,
            'request_at' => Carbon::now(),
        ]);
    }

    /**
     * Batalkan permohonan kehadiran (hanya jika masih pending).
     */
    public function cancelRequest(EmployeeAttendanceRequest $request, Employee $employee): EmployeeAttendanceRequest
    {
        if ($request->employee_id !== $employee->id) {
            throw new InvalidArgumentException('Anda tidak berhak membatalkan permohonan ini.');
        }

        if ($request->request_status !== EmployeeAttendanceRequest::STATUS_PENDING) {
            throw new InvalidArgumentException('Hanya permohonan berstatus pending yang dapat dibatalkan.');
        }

        $request->update([
            'request_status' => EmployeeAttendanceRequest::STATUS_CANCELLED,
        ]);

        return $request;
    }

    /**
     * Setujui permohonan kehadiran oleh Admin/Atasan.
     */
    public function approveRequest(EmployeeAttendanceRequest $request, User $approver, ?string $note = null): EmployeeAttendanceRequest
    {
        if ($request->request_status !== EmployeeAttendanceRequest::STATUS_PENDING) {
            throw new InvalidArgumentException('Hanya permohonan berstatus pending yang dapat disetujui.');
        }

        return DB::transaction(function () use ($request, $approver, $note) {
            $now = Carbon::now();

            $request->update([
                'request_status' => EmployeeAttendanceRequest::STATUS_APPROVED,
                'approved_by' => $approver->id,
                'approved_at' => $now,
                'approval_note' => $note,
            ]);

            // Petakan tipe request ke status presensi
            $statusMap = [
                EmployeeAttendanceRequest::TYPE_IZIN => EmployeeAttendanceSession::STATUS_IZIN, // I
                EmployeeAttendanceRequest::TYPE_SAKIT => EmployeeAttendanceSession::STATUS_SAKIT, // S
                EmployeeAttendanceRequest::TYPE_CUTI => EmployeeAttendanceSession::STATUS_CUTI, // CT
                EmployeeAttendanceRequest::TYPE_DINAS_LUAR => EmployeeAttendanceSession::STATUS_DINAS_LUAR, // DL
            ];

            // Tentukan sesi-sesi tanggal yang terdampak
            $period = CarbonPeriod::create($request->start_at->toDateString(), $request->end_at->toDateString());
            $employee = $request->employee;

            foreach ($period as $date) {
                $dateStr = $date->toDateString();

                // Dapatkan atau buat sesi untuk tanggal tersebut
                try {
                    $session = $this->sessionService->getOrCreateTodaySession($employee, $date);
                } catch (\Throwable $e) {
                    // Jika tanggal bukan hari kerja, abaikan
                    continue;
                }

                // Hubungkan ke pivot employee_attendance_session_requests
                $session->requests()->syncWithoutDetaching([$request->id]);

                // Update status kehadiran jika permohonan mencakup absensi penuh
                if (isset($statusMap[$request->request_type])) {
                    $newStatus = $statusMap[$request->request_type];
                    $oldStatus = $session->attendance_status;

                    $session->update([
                        'attendance_status' => $newStatus,
                        'session_state' => EmployeeAttendanceSession::STATE_CLOSED,
                    ]);

                    EmployeeAttendanceStatusHistory::create([
                        'attendance_session_id' => $session->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'reason' => "Permohonan {$request->request_type} disetujui oleh {$approver->email}.",
                        'changed_by' => $approver->id,
                        'changed_at' => $now,
                    ]);
                }
            }

            return $request;
        });
    }

    /**
     * Tolak permohonan kehadiran.
     */
    public function rejectRequest(EmployeeAttendanceRequest $request, User $approver, string $note): EmployeeAttendanceRequest
    {
        if ($request->request_status !== EmployeeAttendanceRequest::STATUS_PENDING) {
            throw new InvalidArgumentException('Hanya permohonan berstatus pending yang dapat ditolak.');
        }

        $request->update([
            'request_status' => EmployeeAttendanceRequest::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'approved_at' => Carbon::now(),
            'approval_note' => $note,
        ]);

        return $request;
    }
}
