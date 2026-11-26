<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmployeeWorkSchedule;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class WorkScheduleAdminController extends Controller
{
    /**
     * GET /api/v1/admin/work-schedules
     */
    public function index(): JsonResponse
    {
        $schedules = WorkSchedule::with('days')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $schedules,
        ]);
    }

    /**
     * POST /api/v1/admin/work-schedules
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s'],
            'check_in_open_minutes' => ['nullable', 'integer', 'min:0'],
            'late_tolerance_minutes' => ['nullable', 'integer', 'min:0'],
            'evaluation_after_minutes' => ['nullable', 'integer', 'min:0'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'between:1,7'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($validated) {
            $schedule = WorkSchedule::create([
                'name' => $validated['name'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'check_in_open_minutes' => $validated['check_in_open_minutes'] ?? 60,
                'late_tolerance_minutes' => $validated['late_tolerance_minutes'] ?? 15,
                'evaluation_after_minutes' => $validated['evaluation_after_minutes'] ?? 120,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            foreach ($validated['days'] as $day) {
                $schedule->days()->create(['day_of_week' => $day]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Jadwal kerja berhasil dibuat.',
                'data' => $schedule->load('days'),
            ], 201);
        });
    }

    /**
     * GET /api/v1/admin/work-schedules/{id}
     */
    public function show(int $id): JsonResponse
    {
        $schedule = WorkSchedule::with('days')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $schedule,
        ]);
    }

    /**
     * PUT /api/v1/admin/work-schedules/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = WorkSchedule::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'start_time' => ['sometimes', 'date_format:H:i:s'],
            'end_time' => ['sometimes', 'date_format:H:i:s'],
            'check_in_open_minutes' => ['nullable', 'integer', 'min:0'],
            'late_tolerance_minutes' => ['nullable', 'integer', 'min:0'],
            'evaluation_after_minutes' => ['nullable', 'integer', 'min:0'],
            'days' => ['sometimes', 'array', 'min:1'],
            'days.*' => ['integer', 'between:1,7'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($schedule, $validated) {
            $schedule->update([
                'name' => $validated['name'] ?? $schedule->name,
                'start_time' => $validated['start_time'] ?? $schedule->start_time,
                'end_time' => $validated['end_time'] ?? $schedule->end_time,
                'check_in_open_minutes' => $validated['check_in_open_minutes'] ?? $schedule->check_in_open_minutes,
                'late_tolerance_minutes' => $validated['late_tolerance_minutes'] ?? $schedule->late_tolerance_minutes,
                'evaluation_after_minutes' => $validated['evaluation_after_minutes'] ?? $schedule->evaluation_after_minutes,
                'is_active' => $validated['is_active'] ?? $schedule->is_active,
            ]);

            if (isset($validated['days'])) {
                $schedule->days()->delete();
                foreach ($validated['days'] as $day) {
                    $schedule->days()->create(['day_of_week' => $day]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Jadwal kerja berhasil diperbarui.',
                'data' => $schedule->load('days'),
            ]);
        });
    }

    /**
     * POST /api/v1/admin/employee-work-schedules
     */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'work_schedule_id' => ['required', 'exists:work_schedules,id'],
            'effective_from' => ['required', 'date_format:Y-m-d'],
            'effective_until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:effective_from'],
        ]);

        $created = [];

        DB::transaction(function () use ($validated, &$created) {
            foreach ($validated['employee_ids'] as $empId) {
                // Tutup jadwal lama jika masih terbuka tanpa batas akhir
                EmployeeWorkSchedule::where('employee_id', $empId)
                    ->whereNull('effective_until')
                    ->where('effective_from', '<', $validated['effective_from'])
                    ->update([
                        'effective_until' => Carbon::parse($validated['effective_from'])->subDay()->toDateString(),
                    ]);

                $created[] = EmployeeWorkSchedule::create([
                    'employee_id' => $empId,
                    'work_schedule_id' => $validated['work_schedule_id'],
                    'effective_from' => $validated['effective_from'],
                    'effective_until' => $validated['effective_until'] ?? null,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Penugasan jadwal kerja berhasil disimpan.',
            'data' => $created,
        ], 201);
    }
}
