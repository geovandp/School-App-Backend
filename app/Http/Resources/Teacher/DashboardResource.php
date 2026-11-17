<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'teacher' => [
                'id' => $this->resource['teacher']->id,
                'name' => $this->resource['teacher']->user?->profile?->full_name ?? $this->resource['teacher']->user?->email,
                'nip' => $this->resource['teacher']->nip,
                'nuptk' => $this->resource['teacher']->nuptk,
                'employment_status' => $this->resource['teacher']->employment_status,
            ],
            'academic_year' => $this->resource['academic_year'] ? [
                'id' => $this->resource['academic_year']->id,
                'name' => $this->resource['academic_year']->name,
                'is_active' => $this->resource['academic_year']->is_active,
            ] : null,
            'summary' => [
                'total_classrooms' => $this->resource['total_classrooms'],
                'total_courses' => $this->resource['total_courses'],
                'total_assignments' => $this->resource['total_assignments'],
                'total_submissions_pending_review' => $this->resource['total_submissions_pending_review'],
                'total_learning_journals' => $this->resource['total_learning_journals'],
            ],
            'today_schedules' => collect($this->resource['today_schedules'])->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'course_name' => $schedule->teachingAssignment?->course?->name,
                    'course_code' => $schedule->teachingAssignment?->course?->code,
                    'classroom_name' => $schedule->teachingAssignment?->classroom?->name,
                    'room' => $schedule->room ? [
                        'code' => $schedule->room->code,
                        'name' => $schedule->room->name,
                    ] : null,
                    'start_time' => $schedule->startPeriod?->start_time,
                    'end_time' => $schedule->endPeriod?->end_time,
                    'start_period_name' => $schedule->startPeriod?->name,
                    'end_period_name' => $schedule->endPeriod?->name,
                ];
            }),
            'recent_journals' => collect($this->resource['recent_journals'])->map(function ($journal) {
                return [
                    'id' => $journal->id,
                    'learning_date' => $journal->learning_date?->format('Y-m-d'),
                    'topic' => $journal->topic,
                    'classroom_name' => $journal->teachingAssignment?->classroom?->name,
                    'course_name' => $journal->teachingAssignment?->course?->name,
                    'status' => $journal->status,
                ];
            }),
        ];
    }
}
