<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentEnrollment>
 */
class StudentEnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'classroom_id' => Classroom::factory(),
            'enrollment_date' => '2025-07-14',
            'status' => 'active',
            'exit_date' => null,
            'notes' => null,
        ];
    }
}
