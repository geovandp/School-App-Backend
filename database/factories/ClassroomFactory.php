<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\GradeLevel;
use App\Models\Major;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade_level_id' => GradeLevel::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'major_id' => Major::factory(),
            'name' => $this->faker->randomElement([10, 11, 12]) . ' ' . $this->faker->randomElement(['IPA', 'IPS', 'RPL']) . ' ' . $this->faker->randomDigitNotNull(),
            'status' => true,
        ];
    }
}
