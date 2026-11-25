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

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private Teacher $teacher;
    private TeachingAssignment $assignment;
    private SchedulePeriod $period1;
    private SchedulePeriod $period2;
    private Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $teacherRole = Role::create(['nama' => 'teacher']);
        $studentRole = Role::create(['nama' => 'student']);

        $this->teacherUser = User::factory()->create([
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        UserProfile::create([
            'user_id' => $this->teacherUser->id,
            'full_name' => 'Guru Pengajar',
        ]);
        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '198501012010011001',
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
            'code' => 'PROG-10',
            'name' => 'Dasar Pemrograman Web',
            'status' => true,
        ]);

        $this->assignment = TeachingAssignment::create([
            'classroom_id' => $classroom->id,
            'teacher_id' => $this->teacher->id,
            'course_id' => $course->id,
            'status' => 'active',
        ]);

        $this->period1 = SchedulePeriod::create([
            'academic_year_id' => $academicYear->id,
            'period_number' => 1,
            'name' => 'Jam 1',
            'start_time' => '07:00:00',
            'end_time' => '07:45:00',
        ]);

        $this->period2 = SchedulePeriod::create([
            'academic_year_id' => $academicYear->id,
            'period_number' => 2,
            'name' => 'Jam 2',
            'start_time' => '07:45:00',
            'end_time' => '08:30:00',
        ]);

        $this->room = Room::create([
            'code' => 'LAB-RPL',
            'name' => 'Laboratorium RPL',
            'room_type' => 'laboratory',
            'capacity' => 36,
            'status' => true,
        ]);
    }

    public function test_teacher_can_view_all_schedules(): void
    {
        // Buat jadwal untuk teacher 1 di hari Senin
        CourseSchedule::create([
            'teaching_assignment_id' => $this->assignment->id,
            'day_of_week' => 'monday',
            'start_period_id' => $this->period1->id,
            'end_period_id' => $this->period2->id,
            'room_id' => $this->room->id,
            'effective_from' => '2026-07-01',
            'effective_until' => '2027-06-30',
            'status' => 'active',
        ]);

        // Buat jadwal untuk teacher lain di hari yang sama
        $otherTeacherUser = User::factory()->create([
            'role_id' => $this->teacherUser->role_id,
            'status' => 'active',
        ]);
        $otherTeacher = Teacher::create([
            'user_id' => $otherTeacherUser->id,
            'nip' => '999999999999999999',
        ]);
        $otherAssignment = TeachingAssignment::create([
            'classroom_id' => $this->assignment->classroom_id,
            'teacher_id' => $otherTeacher->id,
            'course_id' => $this->assignment->course_id,
            'status' => 'active',
        ]);
        CourseSchedule::create([
            'teaching_assignment_id' => $otherAssignment->id,
            'day_of_week' => 'monday',
            'start_period_id' => $this->period1->id,
            'end_period_id' => $this->period2->id,
            'room_id' => $this->room->id,
            'effective_from' => '2026-07-01',
            'effective_until' => '2027-06-30',
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson('/api/v1/teacher/schedules');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar jadwal mengajar berhasil diambil',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.day_of_week', 'monday')
            ->assertJsonPath('data.0.day_name', 'Senin')
            ->assertJsonPath('data.0.course.code', 'PROG-10')
            ->assertJsonPath('data.0.room.code', 'LAB-RPL');
    }

    public function test_teacher_can_filter_schedules_by_day_of_week(): void
    {
        CourseSchedule::create([
            'teaching_assignment_id' => $this->assignment->id,
            'day_of_week' => 'monday',
            'start_period_id' => $this->period1->id,
            'end_period_id' => $this->period2->id,
            'room_id' => $this->room->id,
            'effective_from' => '2026-07-01',
            'effective_until' => '2027-06-30',
            'status' => 'active',
        ]);

        CourseSchedule::create([
            'teaching_assignment_id' => $this->assignment->id,
            'day_of_week' => 'tuesday',
            'start_period_id' => $this->period1->id,
            'end_period_id' => $this->period2->id,
            'room_id' => $this->room->id,
            'effective_from' => '2026-07-01',
            'effective_until' => '2027-06-30',
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson('/api/v1/teacher/schedules?day_of_week=monday');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.day_of_week', 'monday');
    }

    public function test_teacher_can_view_today_schedules(): void
    {
        $today = strtolower(now()->englishDayOfWeek);

        // Jadwal hari ini
        CourseSchedule::create([
            'teaching_assignment_id' => $this->assignment->id,
            'day_of_week' => $today,
            'start_period_id' => $this->period1->id,
            'end_period_id' => $this->period2->id,
            'room_id' => $this->room->id,
            'effective_from' => now()->subDay()->toDateString(),
            'effective_until' => now()->addDays(30)->toDateString(),
            'status' => 'active',
        ]);

        // Jadwal hari lain (bukan hari ini)
        $otherDay = $today === 'monday' ? 'tuesday' : 'monday';
        CourseSchedule::create([
            'teaching_assignment_id' => $this->assignment->id,
            'day_of_week' => $otherDay,
            'start_period_id' => $this->period1->id,
            'end_period_id' => $this->period2->id,
            'room_id' => $this->room->id,
            'effective_from' => now()->subDay()->toDateString(),
            'effective_until' => now()->addDays(30)->toDateString(),
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson('/api/v1/teacher/schedules/today');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Jadwal mengajar hari ini berhasil diambil',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.day_of_week', $today);
    }

    public function test_expired_schedule_does_not_appear_in_today_schedules(): void
    {
        $today = strtolower(now()->englishDayOfWeek);

        // Jadwal hari ini tapi sudah expired (effective_until kemarin)
        CourseSchedule::create([
            'teaching_assignment_id' => $this->assignment->id,
            'day_of_week' => $today,
            'start_period_id' => $this->period1->id,
            'end_period_id' => $this->period2->id,
            'room_id' => $this->room->id,
            'effective_from' => now()->subDays(30)->toDateString(),
            'effective_until' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson('/api/v1/teacher/schedules/today');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
