<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Resources\Teacher\ScheduleResource;
use App\Models\CourseSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends TeacherController
{
    /**
     * Menampilkan seluruh jadwal mengajar guru yang login.
     * GET /api/v1/teacher/schedules
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $this->getTeacher($request);

        if (! $teacher) {
            return $this->notFoundResponse('Data profil guru tidak ditemukan.');
        }

        $query = CourseSchedule::query()
            ->whereHas('teachingAssignment', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->with([
                'teachingAssignment.course',
                'teachingAssignment.classroom.gradeLevel',
                'teachingAssignment.classroom.major',
                'teachingAssignment.classroom.academicYear',
                'room',
                'startPeriod',
                'endPeriod',
            ]);

        // Filter status (default: active jika tidak ditentukan, atau sesuai query parameter)
        if ($request->filled('status')) {
            $query->where('course_schedules.status', $request->query('status'));
        }

        // Filter hari
        if ($request->filled('day_of_week')) {
            $query->where('course_schedules.day_of_week', strtolower($request->query('day_of_week')));
        }

        // Filter kelas
        if ($request->filled('classroom_id')) {
            $query->whereHas('teachingAssignment', function ($q) use ($request) {
                $q->where('classroom_id', $request->query('classroom_id'));
            });
        }

        // Filter mata pelajaran
        if ($request->filled('course_id')) {
            $query->whereHas('teachingAssignment', function ($q) use ($request) {
                $q->where('course_id', $request->query('course_id'));
            });
        }

        // Filter tahun ajaran
        if ($request->filled('academic_year_id')) {
            $query->whereHas('teachingAssignment.classroom', function ($q) use ($request) {
                $q->where('academic_year_id', $request->query('academic_year_id'));
            });
        }

        $dayOrderSql = "CASE course_schedules.day_of_week
            WHEN 'monday' THEN 1
            WHEN 'tuesday' THEN 2
            WHEN 'wednesday' THEN 3
            WHEN 'thursday' THEN 4
            WHEN 'friday' THEN 5
            WHEN 'saturday' THEN 6
            ELSE 7 END";

        $schedules = $query
            ->join('schedule_periods as sp_start', 'course_schedules.start_period_id', '=', 'sp_start.id')
            ->orderByRaw($dayOrderSql)
            ->orderBy('sp_start.start_time', 'asc')
            ->select('course_schedules.*')
            ->get();

        return $this->successResponse(
            ScheduleResource::collection($schedules),
            'Daftar jadwal mengajar berhasil diambil'
        );
    }

    /**
     * Menampilkan jadwal mengajar guru khusus hari ini.
     * GET /api/v1/teacher/schedules/today
     */
    public function today(Request $request): JsonResponse
    {
        $teacher = $this->getTeacher($request);

        if (! $teacher) {
            return $this->notFoundResponse('Data profil guru tidak ditemukan.');
        }

        $todayDay = strtolower(now()->englishDayOfWeek);
        $todayDate = now()->toDateString();

        $todaySchedules = CourseSchedule::query()
            ->whereHas('teachingAssignment', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->where('course_schedules.status', 'active')
            ->where('course_schedules.day_of_week', $todayDay)
            ->where('course_schedules.effective_from', '<=', $todayDate)
            ->where(function ($query) use ($todayDate) {
                $query->whereNull('course_schedules.effective_until')
                    ->orWhere('course_schedules.effective_until', '>=', $todayDate);
            })
            ->with([
                'teachingAssignment.course',
                'teachingAssignment.classroom.gradeLevel',
                'teachingAssignment.classroom.major',
                'teachingAssignment.classroom.academicYear',
                'room',
                'startPeriod',
                'endPeriod',
            ])
            ->join('schedule_periods as sp_start', 'course_schedules.start_period_id', '=', 'sp_start.id')
            ->orderBy('sp_start.start_time', 'asc')
            ->select('course_schedules.*')
            ->get();

        return $this->successResponse(
            ScheduleResource::collection($todaySchedules),
            'Jadwal mengajar hari ini berhasil diambil'
        );
    }
}
