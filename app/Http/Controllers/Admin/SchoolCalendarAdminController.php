<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolCalendarDay;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolCalendarAdminController extends Controller
{
    /**
     * GET /api/v1/admin/school-calendar
     */
    public function index(Request $request): JsonResponse
    {
        $year = $request->query('year', Carbon::now()->year);
        $month = $request->query('month');

        $query = SchoolCalendarDay::whereYear('calendar_date', $year)
            ->orderBy('calendar_date', 'asc');

        if ($month) {
            $query->whereMonth('calendar_date', $month);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    /**
     * POST /api/v1/admin/school-calendar
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'calendar_date' => ['required', 'date_format:Y-m-d'],
            'day_type' => ['required', 'in:regular,weekend,national_holiday,school_holiday,special_event'],
            'is_working_day' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $day = SchoolCalendarDay::updateOrCreate(
            ['calendar_date' => $validated['calendar_date']],
            [
                'day_type' => $validated['day_type'],
                'is_working_day' => $validated['is_working_day'],
                'description' => $validated['description'] ?? null,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Kalender sekolah berhasil disimpan.',
            'data' => $day,
        ], 200);
    }
}
