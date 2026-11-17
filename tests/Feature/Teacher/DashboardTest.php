<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseSchedule;
use App\Models\GradeLevel;
use App\Models\Major;
use App\Models\Role;
use App\Models\Room;
use App\Models\SchedulePeriod;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Role $teacherRole;
    private Role $studentRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacherRole = Role::create(['nama' => 'teacher']);
        $this->studentRole = Role::create(['nama' => 'student']);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/v1/teacher/dashboard');

        $response->assertStatus(401);
    }

    public function test_student_cannot_access_teacher_dashboard(): void
    {
        $studentUser = User::factory()->create([
            'role_id' => $this->studentRole->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($studentUser);

        $response = $this->getJson('/api/v1/teacher/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini hanya dapat diakses oleh guru.',
            ]);
    }

    public function test_inactive_teacher_cannot_access_dashboard(): void
    {
        $teacherUser = User::factory()->create([
            'role_id' => $this->teacherRole->id,
            'status' => 'suspended',
        ]);

        Sanctum::actingAs($teacherUser);

        $response = $this->getJson('/api/v1/teacher/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
            ]);
    }

    public function test_active_teacher_can_access_dashboard_with_valid_data(): void
    {
        $teacherUser = User::factory()->create([
            'role_id' => $this->teacherRole->id,
            'status' => 'active',
        ]);

        UserProfile::create([
            'user_id' => $teacherUser->id,
            'full_name' => 'Guru Pengajar',
        ]);

        $teacher = Teacher::create([
            'user_id' => $teacherUser->id,
            'nip' => '123456789012345678',
            'employment_status' => 'permanent',
        ]);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);

        $gradeLevel = GradeLevel::create(['level_number' => 10]);
        $major = Major::create(['name' => 'RPL']);

        $classroom = Classroom::create([
            'grade_level_id' => $gradeLevel->id,
            'academic_year_id' => $academicYear->id,
            'major_id' => $major->id,
            'name' => 'X RPL 1',
            'status' => true,
        ]);

        $course = Course::create([
            'code' => 'WEB-1',
            'name' => 'Pemrograman Web',
            'status' => true,
        ]);

        $teachingAssignment = TeachingAssignment::create([
            'classroom_id' => $classroom->id,
            'teacher_id' => $teacher->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $period1 = SchedulePeriod::create([
            'academic_year_id' => $academicYear->id,
            'period_number' => 1,
            'name' => 'Jam 1',
            'start_time' => '07:00:00',
            'end_time' => '07:45:00',
        ]);

        $period2 = SchedulePeriod::create([
            'academic_year_id' => $academicYear->id,
            'period_number' => 2,
            'name' => 'Jam 2',
            'start_time' => '07:45:00',
            'end_time' => '08:30:00',
        ]);

        $room = Room::create([
            'code' => 'R-101',
            'name' => 'Ruang 101',
            'status' => true,
        ]);

        CourseSchedule::create([
            'teaching_assignment_id' => $teachingAssignment->id,
            'day_of_week' => strtolower(now()->englishDayOfWeek),
            'start_period_id' => $period1->id,
            'end_period_id' => $period2->id,
            'room_id' => $room->id,
            'effective_from' => '2026-07-01',
            'effective_until' => '2027-06-30',
            'status' => 'active',
        ]);

        Sanctum::actingAs($teacherUser);

        $response = $this->getJson('/api/v1/teacher/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Data dashboard berhasil diambil',
                'data' => [
                    'teacher' => [
                        'id' => $teacher->id,
                        'name' => 'Guru Pengajar',
                        'nip' => '123456789012345678',
                    ],
                    'summary' => [
                        'total_classrooms' => 1,
                        'total_courses' => 1,
                        'total_assignments' => 0,
                        'total_submissions_pending_review' => 0,
                    ],
                ],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'teacher' => ['id', 'name', 'nip', 'nuptk', 'employment_status'],
                    'academic_year' => ['id', 'name', 'is_active'],
                    'summary' => [
                        'total_classrooms',
                        'total_courses',
                        'total_assignments',
                        'total_submissions_pending_review',
                        'total_learning_journals',
                    ],
                    'today_schedules' => [
                        '*' => [
                            'id',
                            'course_name',
                            'course_code',
                            'classroom_name',
                            'room',
                            'start_time',
                            'end_time',
                        ],
                    ],
                    'recent_journals',
                ],
            ]);
    }
}
