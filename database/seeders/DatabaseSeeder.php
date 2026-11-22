<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\GradeLevel;
use App\Models\Major;
use App\Models\Role;
use App\Models\Student;
use App\Models\Teacher;
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

        // Ambil ID Role berdasarkan kode
        $adminRole = Role::where('code', 'admin')->firstOrFail();
        $teacherRole = Role::where('code', 'teacher')->firstOrFail();
        $administrationRole = Role::where('code', 'school_administration')->firstOrFail();
        $counselorRole = Role::where('code', 'school_counselor')->firstOrFail();
        $studentRole = Role::where('code', 'student')->firstOrFail();
        $alumniRole = Role::where('code', 'alumni')->firstOrFail();

        
        // ==========================================
        // Data Teacher
        // ==========================================

        $teacherProfile = UserProfile::updateOrCreate(
            ['id' => 2],
            [
                'role_id' => $teacherRole->id,
                'full_name' => 'Guru Sekolah',
            ]
        );

        Teacher::updateOrCreate(
            ['profile_id' => $teacherProfile->id],
            []
        );

        User::updateOrCreate(
            ['email' => 'teacher@gmail.com'],
            [
                'profile_id' => $teacherProfile->id,
                'password' => bcrypt('password'),
                'status' => 'pending',
                'email_verified_at' => now(),
            ]
        );

        // ==========================================
        // Data School Administration
        // ==========================================

        $administrationProfile = UserProfile::updateOrCreate(
            ['id' => 3],
            [
                'role_id' => $administrationRole->id,
                'full_name' => 'Staff Administrasi',
            ]
        );

        User::updateOrCreate(
            ['email' => 'tu@gmail.com'],
            [
                'profile_id' => $administrationProfile->id,
                'password' => bcrypt('password'),
                'status' => 'suspended',
                'email_verified_at' => now(),
            ]
        );


        // ==========================================
        // Data School Counselor
        // ==========================================

        $counselorProfile = UserProfile::updateOrCreate(
            ['id' => 4],
            [
                'role_id' => $counselorRole->id,
                'full_name' => 'Guru Bimbingan Konseling',
            ]
        );

        User::updateOrCreate(
            ['email' => 'bk@gmail.com'],
            [
                'profile_id' => $counselorProfile->id,
                'password' => bcrypt('password'),
                'status' => 'suspended',
                'email_verified_at' => now(),
            ]
        );


        // ==========================================
        // Data Alumni
        // ==========================================

        $alumniProfile = UserProfile::updateOrCreate(
            ['id' => 5],
            [
                'role_id' => $alumniRole->id,
                'full_name' => 'Alumni Sekolah',
            ]
        );

        User::updateOrCreate(
            ['email' => 'alumni@gmail.com'],
            [
                'profile_id' => $alumniProfile->id,
                'password' => bcrypt('password'),
                'status' => 'inactive',
                'email_verified_at' => now(),
            ]
        );


        // ==========================================
        // Data Admin
        // ==========================================

        $adminProfile = UserProfile::updateOrCreate(
            ['id' => 1],
            [
                'role_id' => $adminRole->id,
                'full_name' => 'Admin Sekolah',
                'preferred_name' => 'Admin',
                'gender' => 'Male',
                'birth_place' => 'Surabaya',
                'birth_date' => '1945-08-17',
                'address' => 'Jl. Merdeka No. 1, Surabaya',
                'phone' => '081234567890',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'profile_id' => $adminProfile->id,
                'password' => bcrypt('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );


        // ==========================================
        // Data Murid
        // ==========================================

        $studentProfile = UserProfile::updateOrCreate(
            ['id' => 6],
            [
                'role_id' => $studentRole->id,
                'full_name' => 'Murid Rajin',
                'preferred_name' => 'Teladan',
                'gender' => 'Male',
                'birth_place' => 'Surabaya',
                'birth_date' => '2000-01-01',
                'address' => 'Jl. Merdeka No. 2, Surabaya',
                'phone' => '081234567890',
            ]
        );

        $student = Student::updateOrCreate(
            ['profile_id' => $studentProfile->id],
            [
                'nisn' => '1234567890',
                'nis' => '0987654321',
                'admission_year' => 2022,
            ]
        );

        User::updateOrCreate(
            ['email' => 'student@gmail.com'],
            [
                'profile_id' => $studentProfile->id,
                'password' => bcrypt('password'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );


        // ==========================================
        // Data Classroom
        // ==========================================

        $classroom = Classroom::updateOrCreate(
            [
                'name' => 'Kelas 10 IPA 1',
                'grade_level_id' => 1,
                'major_id' => 1,
                'academic_year_id' => 1,
            ],
            [
                'status' => true,
            ]
        );


        // ==========================================
        // Data Student Enrollment
        // ==========================================

        StudentEnrollment::updateOrCreate(
            [
                'student_id' => $student->id,
                'classroom_id' => $classroom->id,
            ],
            [
                'enrollment_date' => '2026-07-01',
                'status' => 'active',
                'exit_date' => null,
                'notes' => null,
            ]
        );

        // User::updateOrCreate([
        //     'role_id' => 2,
        //     'email' => 'teacher@gmail.com',
        //     'password' => bcrypt('password'),
        //     'status' => 'pending',
        //     'email_verified_at' => now(),
        // ]);
        // User::updateOrCreate([
        //     'role_id' => 3,
        //     'email' => 'tu@gmail.com',
        //     'password' => bcrypt('password'),
        //     'status' => 'suspended',
        //     'email_verified_at' => now(),
        // ]);
        // User::updateOrCreate([
        //     'role_id' => 4,
        //     'email' => 'bk@gmail.com',
        //     'password' => bcrypt('password'),
        //     'status' => 'suspended',
        //     'email_verified_at' => now(),
        // ]);
        // User::updateOrCreate([
        //     'role_id' => 6,
        //     'email' => 'alumni@gmail.com',
        //     'password' => bcrypt('password'),
        //     'status' => 'inactive',
        //     'email_verified_at' => now(),
        // ]);

        // // Data Admin
        // User::updateOrCreate(['user_id' => 1], [
        //     'email' => 'admin@gmail.com',
        //     'password' => bcrypt('password'),
        //     'status' => 'active',
        //     'email_verified_at' => now(),
        // ]);
        // UserProfile::updateOrCreate(['user_id' => 1], [
        // // 'role_id' => 1,
        // 'full_name' => 'Admin Sekolah',
        // 'preferred_name' => 'Admin',
        // 'gender' => 'Male',
        // 'birth_place' => 'Surabaya',
        // 'birth_date' => '1945-08-17',
        // 'address' => 'Jl. Merdeka No. 1, Surabaya',
        // 'phone' => '081234567890',
        // ]);

        // // Data Murid
        // User::updateOrCreate(['user_id' => 6], [
        //     // 'role_id' => 5,
        //     'email' => 'student@gmail.com',
        //     'password' => bcrypt('password'),
        //     'status' => 'active',
        //     'email_verified_at' => now(),
        // ]);
        // UserProfile::updateOrCreate(['user_id' => 6], [
        // 'role_id' => 5,
        // 'full_name' => 'Murid rajin',
        // 'preferred_name' => 'Teladan',
        // 'gender' => 'Male',
        // 'birth_place' => 'Surabaya',
        // 'birth_date' => '2000-01-01',
        // 'address' => 'Jl. Merdeka No. 2, Surabaya',
        // 'phone' => '081234567890',
        // ]);
        // Student::updateOrCreate(['user_id' => 6], [
        //     'nisn' => '1234567890',
        //     'nis' => '0987654321',
        //     'admission_year' => '2022'
        // ]);
        // Classroom::updateOrCreate([
        //     'name' => 'Kelas 10 IPA 1',
        //     'grade_level_id' => 1,
        //     'major_id' => 1,
        //     'academic_year_id' => 1,
        // ], [
        //     'status' => 'true'
        // ]);
        // StudentEnrollment::updateOrCreate(['student_id' => 6], [
        //     'student_id' => '6',
        //     'classroom_id' => '1',
        //     'enrollment_date' => "2026-07-01",
        //     'status' => 'active',
        //     'exit_date' => null,
        //     'notes' => null,
        // ]);

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
