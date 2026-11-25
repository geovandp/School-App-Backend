<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $dayNamesIndo = [
            'monday'    => 'Senin',
            'tuesday'   => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday'  => 'Kamis',
            'friday'    => 'Jumat',
            'saturday'  => 'Sabtu',
        ];

        return [
            'id' => $this->id,
            'day_of_week' => $this->day_of_week,
            'day_name' => $dayNamesIndo[$this->day_of_week] ?? ucfirst($this->day_of_week),
            'status' => $this->status,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_until' => $this->effective_until?->format('Y-m-d'),
            'course' => $this->teachingAssignment?->course ? [
                'id' => $this->teachingAssignment->course->id,
                'code' => $this->teachingAssignment->course->code,
                'name' => $this->teachingAssignment->course->name,
                'description' => $this->teachingAssignment->course->description,
            ] : null,
            'classroom' => $this->teachingAssignment?->classroom ? [
                'id' => $this->teachingAssignment->classroom->id,
                'name' => $this->teachingAssignment->classroom->name,
                'grade_level' => $this->teachingAssignment->classroom->gradeLevel?->level_number,
                'major' => $this->teachingAssignment->classroom->major?->name,
                'academic_year' => $this->teachingAssignment->classroom->academicYear?->name,
            ] : null,
            'room' => $this->room ? [
                'id' => $this->room->id,
                'code' => $this->room->code,
                'name' => $this->room->name,
                'room_type' => $this->room->room_type,
                'capacity' => $this->room->capacity,
            ] : null,
            'time' => [
                'start_time' => $this->startPeriod?->start_time,
                'end_time' => $this->endPeriod?->end_time,
                'start_period' => $this->startPeriod ? [
                    'id' => $this->startPeriod->id,
                    'number' => $this->startPeriod->period_number,
                    'name' => $this->startPeriod->name,
                ] : null,
                'end_period' => $this->endPeriod ? [
                    'id' => $this->endPeriod->id,
                    'number' => $this->endPeriod->period_number,
                    'name' => $this->endPeriod->name,
                ] : null,
            ],
            'teaching_assignment_id' => $this->teaching_assignment_id,
        ];
    }
}
