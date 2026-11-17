<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseSchedule;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Pastikan Role Teacher ada
        $teacherRole = Role::firstOrCreate(['nama' => 'teacher']);

        // 2. Buat User Guru Aktif
        $teacherUser = User::updateOrCreate(
            ['email' => 'guru.aktif@sekolah.sch.id'],
            [
                'role_id' => $teacherRole->id,
                'password' => Hash::make('password123'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        // Profil Guru
        UserProfile::updateOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'full_name' => 'Budi Santoso, S.Pd.',
                'preferred_name' => 'Pak Budi',
                'gender' => 'Male',
                'birth_place' => 'Jakarta',
                'birth_date' => '1985-05-12',
                'address' => 'Jl. Pendidikan No. 45',
                'phone' => '081298765432',
            ]
        );

        // Data Record Teacher
        $teacher = Teacher::updateOrCreate(
            ['user_id' => $teacherUser->id],
            [
                'nip' => '198505122010011002',
                'nuptk' => '4536763665200002',
                'employment_status' => 'permanent',
                'hire_date' => '2010-01-01',
            ]
        );

        // 3. Pastikan Master Akademik Ada
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

        // 4. Master Course (Mata Pelajaran)
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

        // 5. Teaching Assignments
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

        // 6. Master Ruangan & Jam Pelajaran
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

        $period3 = SchedulePeriod::firstOrCreate(
            [
                'academic_year_id' => $academicYear->id,
                'period_number' => 3,
            ],
            [
                'name' => 'Jam Ke-3',
                'start_time' => '08:45:00',
                'end_time' => '09:30:00',
            ]
        );

        // 7. Jadwal Pelajaran (termasuk jadwal hari ini berdasarkan nama hari)
        $todayDay = strtolower(now()->englishDayOfWeek); // e.g. tuesday, monday, etc.

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

        // 8. Dummy Siswa & Enrollment untuk kelas X RPL 1
        $studentRole = Role::firstOrCreate(['nama' => 'student']);
        for ($i = 1; $i <= 3; $i++) {
            $studentUser = User::updateOrCreate(
                ['email' => "siswa{$i}@sekolah.sch.id"],
                [
                    'role_id' => $studentRole->id,
                    'password' => Hash::make('password123'),
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );

            UserProfile::updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'full_name' => "Siswa Siswi {$i}",
                    'preferred_name' => "Siswa {$i}",
                    'gender' => $i % 2 === 0 ? 'Female' : 'Male',
                ]
            );

            $student = Student::updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'nisn' => '009876543' . $i,
                    'nis' => '202600' . $i,
                    'admission_year' => 2026,
                ]
            );

            StudentEnrollment::updateOrCreate(
                [
                    'student_id' => $student->user_id,
                    'classroom_id' => $classroom1->id,
                ],
                [
                    'enrollment_date' => '2026-07-15',
                    'status' => 'active',
                ]
            );
        }

        // 9. Jurnal Pembelajaran Dummy
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

        // 10. Skills
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
