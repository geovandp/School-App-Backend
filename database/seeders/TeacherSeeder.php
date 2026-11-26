<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\GradeLevel;
use App\Models\LearningJournal;
use App\Models\Major;
use App\Models\Role;
use App\Models\Room;
use App\Models\SchedulePeriod;
use App\Models\Skill;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\TeacherSkill;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles (Teacher, Student, Admin)
        $teacherRole = Role::where('code', 'teacher')->orWhere('name', 'teacher')->first()
            ?? Role::create(['code' => 'teacher', 'name' => 'Guru']);

        $studentRole = Role::where('code', 'student')->orWhere('name', 'student')->first()
            ?? Role::create(['code' => 'student', 'name' => 'Siswa']);

        $adminRole = Role::where('code', 'admin')->orWhere('name', 'admin')->first()
            ?? Role::create(['code' => 'admin', 'name' => 'Administrator']);

        // 2. Akun Guru Aktif
        $teacherUserData = [
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ];
        if (Schema::hasColumn('users', 'role_id')) {
            $teacherUserData['role_id'] = $teacherRole->id;
        }

        $teacherUser = User::updateOrCreate(
            ['email' => 'guru.aktif@sekolah.sch.id'],
            $teacherUserData
        );

        if (method_exists($teacherUser, 'roles') && Schema::hasTable('role_user')) {
            $teacherUser->roles()->syncWithoutDetaching([$teacherRole->id]);
        }

        // Profil Guru
        $teacherProfileData = [
            'full_name' => 'Budi Santoso, S.Pd.',
            'preferred_name' => 'Pak Budi',
            'gender' => 'Male',
            'birth_place' => 'Jakarta',
            'birth_date' => '1985-05-12',
            'address' => 'Jl. Pendidikan No. 45',
            'phone' => '081298765432',
        ];
        if (Schema::hasColumn('user_profiles', 'role_id')) {
            $teacherProfileData['role_id'] = $teacherRole->id;
        }

        $teacherProfile = UserProfile::updateOrCreate(
            ['user_id' => $teacherUser->id],
            $teacherProfileData
        );

        if (Schema::hasColumn('users', 'profile_id')) {
            $teacherUser->update(['profile_id' => $teacherProfile->id]);
        }

        // 3. Employee & Modul Presensi (Attendance Setup)
        $employee = null;
        if (Schema::hasTable('employees')) {
            $employee = Employee::updateOrCreate(
                ['user_id' => $teacherUser->id],
                [
                    'employee_number' => 'EMP-198505122010011002',
                    'employment_status' => 'permanent',
                ]
            );
        }

        // Record Teacher
        $teacherData = [
            'nip' => '198505122010011002',
            'nuptk' => '4536763665200002',
            'employment_status' => 'permanent',
            'hire_date' => '2010-01-01',
        ];
        if (Schema::hasColumn('teachers', 'profile_id')) {
            $teacherData['profile_id'] = $teacherProfile->id;
        }
        if ($employee && Schema::hasColumn('teachers', 'employee_id')) {
            $teacherData['employee_id'] = $employee->id;
        }

        $teacher = Teacher::updateOrCreate(
            ['user_id' => $teacherUser->id],
            $teacherData
        );

        // Jadwal Kerja Presensi (Work Schedule)
        if ($employee && Schema::hasTable('work_schedules') && Schema::hasTable('employee_work_schedules')) {
            $schedule = WorkSchedule::firstOrCreate(
                ['name' => 'Jadwal Reguler Guru'],
                [
                    'start_time' => '07:00:00',
                    'end_time' => '15:00:00',
                    'check_in_open_minutes' => 60,
                    'late_tolerance_minutes' => 15,
                    'evaluation_after_minutes' => 120,
                    'is_active' => true,
                ]
            );

            if (Schema::hasTable('work_schedule_days')) {
                // Hari Senin - Minggu (1-7) aktif agar pengujian presensi di hari apa pun selalu valid
                for ($day = 1; $day <= 7; $day++) {
                    WorkScheduleDay::firstOrCreate([
                        'work_schedule_id' => $schedule->id,
                        'day_of_week' => $day,
                    ]);
                }
            }

            EmployeeWorkSchedule::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'work_schedule_id' => $schedule->id,
                ],
                [
                    'effective_from' => '2026-01-01',
                    'effective_until' => null,
                ]
            );
        }

        // 4. Akun Admin (Untuk Pengujian Approval & Rekap Presensi)
        $adminUserData = [
            'password' => Hash::make('password123'),
            'status' => 'active',
            'email_verified_at' => now(),
        ];
        if (Schema::hasColumn('users', 'role_id')) {
            $adminUserData['role_id'] = $adminRole->id;
        }

        $adminUser = User::updateOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            $adminUserData
        );

        if (method_exists($adminUser, 'roles') && Schema::hasTable('role_user')) {
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $adminProfileData = [
            'full_name' => 'Administrator Sekolah',
            'preferred_name' => 'Admin',
            'gender' => 'Male',
            'phone' => '081234567890',
        ];
        if (Schema::hasColumn('user_profiles', 'role_id')) {
            $adminProfileData['role_id'] = $adminRole->id;
        }

        $adminProfile = UserProfile::updateOrCreate(
            ['user_id' => $adminUser->id],
            $adminProfileData
        );

        if (Schema::hasColumn('users', 'profile_id')) {
            $adminUser->update(['profile_id' => $adminProfile->id]);
        }

        // 5. Master Akademik
        $academicYear = AcademicYear::firstOrCreate(
            ['start_date' => '2026-07-15'],
            [
                'name' => '2026/2027 Ganjil',
                'end_date' => '2026-12-20',
                'is_active' => true,
            ]
        );

        $gradeLevel = GradeLevel::firstOrCreate(['level_number' => 10]);
        $major = Major::firstOrCreate(['name' => 'Rekayasa Perangkat Lunak']);

        $classroom1 = Classroom::firstOrCreate(
            ['name' => 'X RPL 1', 'academic_year_id' => $academicYear->id],
            [
                'grade_level_id' => $gradeLevel->id,
                'major_id' => $major->id,
                'status' => true,
            ]
        );

        $classroom2 = Classroom::firstOrCreate(
            ['name' => 'X RPL 2', 'academic_year_id' => $academicYear->id],
            [
                'grade_level_id' => $gradeLevel->id,
                'major_id' => $major->id,
                'status' => true,
            ]
        );

        // 6. Master Mata Pelajaran (Course)
        $course1 = Course::firstOrCreate(
            ['code' => 'PROG-10'],
            [
                'name' => 'Dasar Pemrograman Web',
                'description' => 'Mempelajari HTML, CSS, JavaScript dan dasar backend',
                'status' => true,
            ]
        );

        $course2 = Course::firstOrCreate(
            ['code' => 'DB-10'],
            [
                'name' => 'Basis Data Relasional',
                'description' => 'Mempelajari ERD, SQL dan perancangan database',
                'status' => true,
            ]
        );

        // 7. Penugasan Mengajar (Teaching Assignments)
        $assignment1 = TeachingAssignment::firstOrCreate(
            [
                'classroom_id' => $classroom1->id,
                'teacher_id' => $teacher->id,
                'course_id' => $course1->id,
            ],
            ['status' => 'active']
        );

        $assignment2 = TeachingAssignment::firstOrCreate(
            [
                'classroom_id' => $classroom2->id,
                'teacher_id' => $teacher->id,
                'course_id' => $course2->id,
            ],
            ['status' => 'active']
        );

        // 8. Ruangan & Jam Pelajaran
        $room1 = Room::firstOrCreate(
            ['code' => 'LAB-RPL'],
            [
                'name' => 'Laboratorium Komputer RPL',
                'room_type' => 'laboratory',
                'capacity' => 36,
                'status' => true,
            ]
        );

        $period1 = SchedulePeriod::firstOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'period_number' => 1,
            ],
            [
                'name' => 'Jam Ke-1',
                'start_time' => '07:00:00',
                'end_time' => '07:45:00',
            ]
        );

        $period2 = SchedulePeriod::firstOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'period_number' => 2,
            ],
            [
                'name' => 'Jam Ke-2',
                'start_time' => '07:45:00',
                'end_time' => '08:30:00',
            ]
        );

        // 9. Jadwal Mengajar Hari Ini
        $todayDay = strtolower(now()->englishDayOfWeek);

        CourseSchedule::updateOrCreate(
            [
                'teaching_assignment_id' => $assignment1->id,
                'day_of_week' => $todayDay,
                'start_period_id' => $period1->id,
            ],
            [
                'end_period_id' => $period2->id,
                'room_id' => $room1->id,
                'effective_from' => '2026-07-15',
                'effective_until' => '2026-12-20',
                'status' => 'active',
            ]
        );

        // 10. Siswa & Enrollment Kelas
        for ($i = 1; $i <= 3; $i++) {
            $studentUserData = [
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ];
            if (Schema::hasColumn('users', 'role_id')) {
                $studentUserData['role_id'] = $studentRole->id;
            }

            $studentUser = User::updateOrCreate(
                ['email' => "siswa{$i}@sekolah.sch.id"],
                $studentUserData
            );

            if (method_exists($studentUser, 'roles') && Schema::hasTable('role_user')) {
                $studentUser->roles()->syncWithoutDetaching([$studentRole->id]);
            }

            $studentProfileData = [
                'full_name' => "Siswa Siswi {$i}",
                'preferred_name' => "Siswa {$i}",
                'gender' => $i % 2 === 0 ? 'Female' : 'Male',
            ];
            if (Schema::hasColumn('user_profiles', 'role_id')) {
                $studentProfileData['role_id'] = $studentRole->id;
            }

            $studentProfile = UserProfile::updateOrCreate(
                ['user_id' => $studentUser->id],
                $studentProfileData
            );

            if (Schema::hasColumn('users', 'profile_id')) {
                $studentUser->update(['profile_id' => $studentProfile->id]);
            }

            $studentData = [
                'nisn' => '009876543' . $i,
                'nis' => '202600' . $i,
                'admission_year' => 2026,
            ];
            if (Schema::hasColumn('students', 'profile_id')) {
                $studentData['profile_id'] = $studentProfile->id;
            }

            $student = Student::updateOrCreate(
                ['user_id' => $studentUser->id],
                $studentData
            );

            StudentEnrollment::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'classroom_id' => $classroom1->id,
                ],
                [
                    'enrollment_date' => '2026-07-15',
                    'status' => 'active',
                ]
            );
        }

        // 11. Jurnal Pembelajaran
        LearningJournal::updateOrCreate(
            [
                'teaching_assignment_id' => $assignment1->id,
                'created_by' => $teacherUser->id,
                'learning_date' => now()->toDateString(),
            ],
            [
                'topic' => 'Pengenalan Sintaks PHP & MVC',
                'learning_objective' => 'Siswa memahami struktur dasar arsitektur MVC',
                'learning_material' => 'Routing, Controller, View',
                'learning_activities' => 'Praktik membuat endpoint dasar',
                'learning_outcomes' => 'Semua siswa berhasil membuat route GET',
                'notes' => 'Kelas berlangsung kondusif',
                'status' => 'submitted',
            ]
        );

        // 12. Keahlian Guru (Skills)
        $skill1 = Skill::firstOrCreate(['name' => 'Web Development']);
        $skill2 = Skill::firstOrCreate(['name' => 'Database Design']);

        TeacherSkill::firstOrCreate(
            [
                'teacher_id' => $teacher->id,
                'skill_id' => $skill1->id,
            ],
            [
                'proficiency_level' => 'expert',
                'years_experience' => 8,
                'verification_status' => 'verified',
            ]
        );
    }
}
