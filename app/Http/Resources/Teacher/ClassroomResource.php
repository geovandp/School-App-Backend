<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'grade_level' => $this->gradeLevel ? [
                'id' => $this->gradeLevel->id,
                'level_number' => $this->gradeLevel->level_number,
            ] : null,
            'major' => $this->major ? [
                'id' => $this->major->id,
                'name' => $this->major->name,
            ] : null,
            'academic_year' => $this->academicYear ? [
                'id' => $this->academicYear->id,
                'name' => $this->academicYear->name,
                'is_active' => $this->academicYear->is_active,
            ] : null,
            'active_students_count' => $this->when(
                isset($this->active_students_count),
                $this->active_students_count,
                fn () => $this->enrollments()->where('status', 'active')->count()
            ),
            'courses' => $this->whenLoaded('teachingAssignments', function () use ($request) {
                $teacherId = $request->user()->teacher?->id;
                return $this->teachingAssignments
                    ->when($teacherId, fn ($collection) => $collection->where('teacher_id', $teacherId))
                    ->map(fn ($assignment) => [
                        'assignment_id' => $assignment->id,
                        'course_id' => $assignment->course?->id,
                        'course_code' => $assignment->course?->code,
                        'course_name' => $assignment->course?->name,
                    ])->values();
            }),
        ];
    }
}
