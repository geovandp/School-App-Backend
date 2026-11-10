<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::factory(3)->create();

        // Membuat 1 akun Admin
        User::factory()->active()->create([
            'role_id' => 2,
            'email' => 'guru@gmail.com',
            'password' => 'password',
            'status' => 'pending',
            'email_verified_at' => now(),
        ]);
        User::factory()->active()->create([
            'role_id' => 1,
            'email' => 'admin@gmail.com',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        User::factory()->active()->create([
            'role_id' => 3,
            'email' => 'murid@gmail.com',
            'password' => 'password',
            'status' => 'suspended',
            'email_verified_at' => now(),
        ]);
    }
}
