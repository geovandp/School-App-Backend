<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => '2025/2026',
            'start_date' => '2025-07-14',
            'end_date' => '2026-06-26',
            'is_active' => true,
        ];
    }
}
