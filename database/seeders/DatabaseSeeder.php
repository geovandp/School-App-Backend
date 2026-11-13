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
        // 1. Buat Roles (6 Role sesuai kebutuhan sistem)
        $roles = ['admin', 'teacher', 'school administration', 'school counselor', 'student', 'alumni'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['nama' => $roleName]);
        }

        // Ambil ID role berdasarkan nama untuk kepastian mapping
        $adminRole = Role::where('nama', 'admin')->first();
        $teacherRole = Role::where('nama', 'teacher')->first();
        $studentRole = Role::where('nama', 'student')->first();

        // 2. Buat Master Data (Academic Year, Grade Levels, Majors, Classroom)
        $academicYear = AcademicYear::factory()->create([
            'is_active' => true,
        ]);

        $gradeLevel = GradeLevel::factory()->create([
            'level_number' => 10,
        ]);

        $major = Major::factory()->create([
            'name' => 'Rekayasa Perangkat Lunak (RPL)',
        ]);

        $classroom = Classroom::factory()->create([
            'grade_level_id' => $gradeLevel->id,
            'academic_year_id' => $academicYear->id,
            'major_id' => $major->id,
            'name' => '10 RPL 1',
        ]);

        // 3. Membuat Akun Admin
        User::factory()->create([
            'role_id' => $adminRole ? $adminRole->id : 1,
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ])->each(function ($user) {
            UserProfile::factory()->create(['user_id' => $user->id, 'full_name' => 'Administrator Sistem']);
        });

        // 4. Membuat Akun Guru (Teacher)
        User::factory()->create([
            'role_id' => $teacherRole ? $teacherRole->id : 2,
            'email' => 'guru@gmail.com',
            'password' => Hash::make('password'),
            'status' => 'active',
            'email_verified_at' => now(),
        ])->each(function ($user) {
            UserProfile::factory()->create(['user_id' => $user->id, 'full_name' => 'Budi Santoso, S.Pd.']);
        });

        // 5. Membuat Akun Murid (Student) beserta Profile, Student Record, dan Enrollment ke Classroom
        $muridUser = User::factory()->create([
            'role_id' => $studentRole ? $studentRole->id : 5,
            'email' => 'murid@gmail.com',
            'password' => Hash::make('password'),
            'status' => 'active', // disesuaikan agar bisa login uji coba
            'email_verified_at' => now(),
        ]);

        // Buat User Profile untuk murid
        UserProfile::factory()->create([
            'user_id' => $muridUser->id,
            'full_name' => 'Andi Pratama',
        ]);

        // Buat data tabel students
        $studentRecord = Student::factory()->create([
            'user_id' => $muridUser->id,
        ]);

        // Masukkan murid ke tabel enrollments (masuk ke kelas 10 RPL 1)
        StudentEnrollment::factory()->create([
            'student_id' => $studentRecord->user_id,
            'classroom_id' => $classroom->id,
            'status' => 'active',
        ]);
    }
}

// namespace Database\Seeders;

// use App\Models\User;
// use App\Models\Role;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
// use Illuminate\Database\Seeder;

// class DatabaseSeeder extends Seeder
// {
//     use WithoutModelEvents;

//     /**
//      * Seed the application's database.
//      */
//     public function run(): void
//     {
//         Role::factory(6)->create();

//         // Membuat 1 akun Admin
//         User::factory()->active()->create([
//             'role_id' => 2,
//             'email' => 'guru@gmail.com',
//             'password' => 'password',
//             'status' => 'pending',
//             'email_verified_at' => now(),
//         ]);
//         User::factory()->active()->create([
//             'role_id' => 1,
//             'email' => 'admin@gmail.com',
//             'password' => 'password',
//             'status' => 'active',
//             'email_verified_at' => now(),
//         ]);
//         User::factory()->active()->create([
//             'role_id' => 3,
//             'email' => 'murid@gmail.com',
//             'password' => 'password',
//             'status' => 'suspended',
//             'email_verified_at' => now(),
//         ]);
//     }
// }
