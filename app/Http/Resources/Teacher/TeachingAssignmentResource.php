<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeachingAssignmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'classroom' => $this->classroom ? [
                'id' => $this->classroom->id,
                'name' => $this->classroom->name,
                'status' => $this->classroom->status,
                'grade_level' => $this->classroom->gradeLevel ? [
                    'id' => $this->classroom->gradeLevel->id,
                    'level_number' => $this->classroom->gradeLevel->level_number,
                ] : null,
                'major' => $this->classroom->major ? [
                    'id' => $this->classroom->major->id,
                    'name' => $this->classroom->major->name,
                ] : null,
                'academic_year' => $this->classroom->academicYear ? [
                    'id' => $this->classroom->academicYear->id,
                    'name' => $this->classroom->academicYear->name,
                    'is_active' => $this->classroom->academicYear->is_active,
                ] : null,
            ] : null,
            'course' => $this->course ? [
                'id' => $this->course->id,
                'code' => $this->course->code,
                'name' => $this->course->name,
                'description' => $this->course->description,
            ] : null,
            'schedules' => $this->whenLoaded('schedules', function () {
                return $this->schedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'day_of_week' => $schedule->day_of_week,
                        'room' => $schedule->room ? [
                            'code' => $schedule->room->code,
                            'name' => $schedule->room->name,
                        ] : null,
                        'start_period' => $schedule->startPeriod ? [
                            'number' => $schedule->startPeriod->period_number,
                            'name' => $schedule->startPeriod->name,
                            'time' => $schedule->startPeriod->start_time,
                        ] : null,
                        'end_period' => $schedule->endPeriod ? [
                            'number' => $schedule->endPeriod->period_number,
                            'name' => $schedule->endPeriod->name,
                            'time' => $schedule->endPeriod->end_time,
                        ] : null,
                    ];
                });
            }),
            'students_count' => $this->when(
                isset($this->students_count) || $this->relationLoaded('classroom'),
                fn () => $this->students_count ?? $this->classroom?->enrollments()->where('status', 'active')->count()
            ),
        ];
    }
}
