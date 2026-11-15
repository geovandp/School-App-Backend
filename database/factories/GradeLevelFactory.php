<?php

namespace Database\Factories;

use App\Models\GradeLevel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeLevel>
 */
class GradeLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $levels = ['10', '11', '12'];
        $level_numbers = array_shift($levels);

        return [
            'level_number' => $level_numbers,
        ];

        // return [
        //     'level_number' => $this->faker->randomElement([10, 11, 12]),
        // ];
    }
}
