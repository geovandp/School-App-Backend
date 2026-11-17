<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassroomStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $student = $this->student;
        $profile = $student?->user?->profile;

        return [
            'enrollment_id' => $this->id,
            'student_id' => $this->student_id,
            'nisn' => $student?->nisn,
            'nis' => $student?->nis,
            'admission_year' => $student?->admission_year,
            'full_name' => $profile?->full_name ?? $student?->user?->email,
            'preferred_name' => $profile?->preferred_name,
            'gender' => $profile?->gender,
            'avatar_path' => $profile?->avatar_path,
            'enrollment_date' => $this->enrollment_date?->format('Y-m-d'),
            'enrollment_status' => $this->status,
        ];
    }
}
