<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserProfile>
 */
class UserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => $this->faker->name(),
            'preferred_name' => $this->faker->firstName(),
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'birth_place' => $this->faker->city(),
            'birth_date' => $this->faker->date('Y-m-d', '2010-01-01'),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'avatar_path' => null,
        ];
    }
}
