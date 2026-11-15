<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\GradeLevel;
use App\Models\Major;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::factory(6)->create();
        AcademicYear::factory(1)->create();
        GradeLevel::factory(3)->create();
        Major::factory(5)->create();

        User::updateOrCreate([
            'role_id' => 2,
            'email' => 'teacher@gmail.com',
            'password' => bcrypt('password'),
            'status' => 'pending',
            'email_verified_at' => now(),
        ]);
        User::updateOrCreate([
            'role_id' => 3,
            'email' => 'tu@gmail.com',
            'password' => bcrypt('password'),
            'status' => 'suspended',
            'email_verified_at' => now(),
        ]);
        User::updateOrCreate([
            'role_id' => 4,
            'email' => 'bk@gmail.com',
            'password' => bcrypt('password'),
            'status' => 'suspended',
            'email_verified_at' => now(),
        ]);
        User::updateOrCreate([
            'role_id' => 6,
            'email' => 'alumni@gmail.com',
            'password' => bcrypt('password'),
            'status' => 'inactive',
            'email_verified_at' => now(),
        ]);

        // Data Admin
        User::updateOrCreate([
            'role_id' => 1,
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        UserProfile::updateOrCreate(['user_id' => 1], [
        'full_name' => 'Admin Sekolah',
        'preferred_name' => 'Admin',
        'gender' => 'Male',
        'birth_place' => 'Surabaya',
        'birth_date' => '1945-08-17',
        'address' => 'Jl. Merdeka No. 1, Surabaya',
        'phone' => '081234567890',
        ]);

        // Data Murid
        User::updateOrCreate([
            'role_id' => 5,
            'email' => 'student@gmail.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        UserProfile::updateOrCreate(['user_id' => 6], [
        'full_name' => 'Murid rajin',
        'preferred_name' => 'Teladan',
        'gender' => 'Male',
        'birth_place' => 'Surabaya',
        'birth_date' => '2000-01-01',
        'address' => 'Jl. Merdeka No. 2, Surabaya',
        'phone' => '081234567890',
        ]);
        Student::updateOrCreate(['user_id' => 6], [
            'nisn' => '1234567890',
            'nis' => '0987654321',
            'admission_year' => '2022'
        ]);
        Classroom::updateOrCreate([
            'name' => 'Kelas 10 IPA 1',
            'grade_level_id' => 1,
            'major_id' => 1,
            'academic_year_id' => 1,
        ], [
            'status' => 'true'
        ]);
        StudentEnrollment::updateOrCreate(['student_id' => 6], [
            'student_id' => '6',
            'classroom_id' => '1',
            'enrollment_date' => "2026-07-01",
            'status' => 'active',
            'exit_date' => null,
            'notes' => null,
        ]);

        // // Membuat 1 akun Admin
        // User::factory()->active()->create([
        //     'role_id' => 2,
        //     'email' => 'guru@gmail.com',
        //     'password' => 'password',
        //     'status' => 'pending',
        //     'email_verified_at' => now(),
        // ]);
        // User::factory()->active()->create([
        //     'role_id' => 1,
        //     'email' => 'admin@gmail.com',
        //     'password' => 'password',
        //     'status' => 'active',
        //     'email_verified_at' => now(),
        // ]);
        // User::factory()->active()->create([
        //     'role_id' => 3,
        //     'email' => 'murid@gmail.com',
        //     'password' => 'password',
        //     'status' => 'suspended',
        //     'email_verified_at' => now(),
        // ]);
    }
}
