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
            'email' => 'admin@example.com',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
