<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $roles = [
            [
                'code' => 'admin',
                'name' => 'Admin',
            ],
            [
                'code' => 'teacher',
                'name' => 'Teacher',
            ],
            [
                'code' => 'school_administration',
                'name' => 'School Administration',
            ],
            [
                'code' => 'school_counselor',
                'name' => 'School Counselor',
            ],
            [
                'code' => 'student',
                'name' => 'Student',
            ],
            [
                'code' => 'alumni',
                'name' => 'Alumni',
            ],
        ];

        $role = array_shift($roles);

        return $role ?? [
            'code' => fake()->unique()->slug(2),
            'name' => fake()->jobTitle(),
        ];
    }
}
