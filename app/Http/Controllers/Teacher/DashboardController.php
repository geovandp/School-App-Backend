<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Resources\Teacher\DashboardResource;
use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\CourseSchedule;
use App\Models\LearningJournal;
use App\Models\Submission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends TeacherController
{
    public function index(Request $request): JsonResponse
    {
        $teacher = $this->getTeacher($request);

        if (! $teacher) {
            return $this->notFoundResponse('Data profil guru tidak ditemukan. Silakan hubungi admin.');
        }

        $teacher->loadMissing(['user.profile']);

        // 1. Tahun ajaran aktif
        $activeAcademicYear = AcademicYear::where('is_active', true)->first();

        // 2. ID Teaching Assignments aktif milik guru
        $teachingAssignmentIds = $teacher->teachingAssignments()
            ->where('status', 'active')
            ->pluck('id');

        // 3. Ringkasan jumlah kelas dan mata pelajaran yang diajar
        $totalClassrooms = $teacher->teachingAssignments()
            ->where('status', 'active')
            ->distinct('classroom_id')
            ->count('classroom_id');

        $totalCourses = $teacher->teachingAssignments()
            ->where('status', 'active')
            ->distinct('course_id')
            ->count('course_id');

        // 4. Jadwal hari ini
        $todayDay = strtolower(now()->englishDayOfWeek);
        $todayDate = now()->toDateString();

        $todaySchedules = CourseSchedule::query()
            ->whereIn('teaching_assignment_id', $teachingAssignmentIds)
            ->where('status', 'active')
            ->where('day_of_week', $todayDay)
            ->where('effective_from', '<=', $todayDate)
            ->where(function ($query) use ($todayDate) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $todayDate);
            })
            ->with([
                'teachingAssignment.course',
                'teachingAssignment.classroom',
                'room',
                'startPeriod',
                'endPeriod',
            ])
            ->join('schedule_periods as sp_start', 'course_schedules.start_period_id', '=', 'sp_start.id')
            ->orderBy('sp_start.start_time', 'asc')
            ->select('course_schedules.*')
            ->get();

        // 5. Total tugas yang dibuat
        $assignmentIds = Assignment::where('created_by', $teacher->id)->pluck('id');
        $totalAssignments = $assignmentIds->count();

        // 6. Submissions yang menunggu direview (status: submitted atau late)
        $totalSubmissionsPendingReview = Submission::whereHas('recipient', function ($q) use ($assignmentIds) {
            $q->whereIn('assignment_id', $assignmentIds);
        })
        ->whereIn('status', ['submitted', 'late'])
        ->count();

        // 7. Jurnal pembelajaran
        $totalLearningJournals = LearningJournal::where('created_by', $request->user()->id)->count();

        $recentJournals = LearningJournal::query()
            ->where('created_by', $request->user()->id)
            ->with(['teachingAssignment.classroom', 'teachingAssignment.course'])
            ->latest('learning_date')
            ->limit(5)
            ->get();

        $data = [
            'teacher' => $teacher,
            'academic_year' => $activeAcademicYear,
            'total_classrooms' => $totalClassrooms,
            'total_courses' => $totalCourses,
            'total_assignments' => $totalAssignments,
            'total_submissions_pending_review' => $totalSubmissionsPendingReview,
            'total_learning_journals' => $totalLearningJournals,
            'today_schedules' => $todaySchedules,
            'recent_journals' => $recentJournals,
        ];

        return $this->successResponse(new DashboardResource($data), 'Data dashboard berhasil diambil');
    }
}
