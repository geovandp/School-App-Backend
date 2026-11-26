<?php

namespace Tests\Feature\Teacher;

use App\Models\Employee;
use App\Models\EmployeeWorkSchedule;
use App\Models\Role;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private Teacher $teacher;
    private Employee $employee;
    private WorkSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $teacherRole = Role::where('code', 'teacher')->first() ?? Role::create(['code' => 'teacher', 'name' => 'Teacher']);
        $adminRole = Role::where('code', 'admin')->first() ?? Role::create(['code' => 'admin', 'name' => 'Administrator']);

        // 2. User Teacher
        $this->teacherUser = User::factory()->create([
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        $this->teacherUser->roles()->attach($teacherRole->id);

        UserProfile::create([
            'user_id' => $this->teacherUser->id,
            'full_name' => 'Budi Santoso, S.Pd',
        ]);

        // 3. Employee & Teacher
        $this->employee = Employee::create([
            'user_id' => $this->teacherUser->id,
            'employee_number' => 'NIP-198501012010011001',
            'employment_status' => 'permanent',
        ]);

        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'employee_id' => $this->employee->id,
            'nip' => '198501012010011001',
            'nuptk' => '1234567890123456',
            'employment_status' => 'permanent',
            'hire_date' => '2010-01-01',
        ]);

        // 4. Work Schedule: Senin-Jumat 07:00 - 15:00
        $this->schedule = WorkSchedule::create([
            'name' => 'Jadwal Reguler Guru',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
            'check_in_open_minutes' => 60,
            'late_tolerance_minutes' => 15,
            'early_leave_tolerance_minutes' => 0,
            'is_active' => true,
        ]);

        for ($day = 1; $day <= 5; $day++) {
            WorkScheduleDay::create([
                'work_schedule_id' => $this->schedule->id,
                'day_of_week' => $day,
            ]);
        }

        EmployeeWorkSchedule::create([
            'employee_id' => $this->employee->id,
            'work_schedule_id' => $this->schedule->id,
            'effective_from' => '2026-01-01',
            'effective_until' => null,
        ]);
    }

    public function test_teacher_can_check_in_attendance_within_1km(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 06:55:00')); // Wednesday
        Sanctum::actingAs($this->teacherUser);

        // School coordinates: -6.2088, 106.8456
        $response = $this->postJson('/api/v1/attendance/check-in', [
            'latitude' => -6.2090,
            'longitude' => 106.8457, // ~25 meters
            'device_identifier' => 'device-teacher-01',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.event_type', 'check_in');

        $this->assertDatabaseHas('employee_attendance_sessions', [
            'employee_id' => $this->employee->id,
            'attendance_status' => 'H',
        ]);
    }

    public function test_teacher_can_view_today_attendance_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-25 07:00:00'));
        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson('/api/v1/attendance/today');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'session_id',
                    'attendance_date',
                    'session_state',
                    'attendance_status',
                    'schedule',
                    'events',
                ],
            ]);
    }

    public function test_teacher_can_submit_permit_request(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $response = $this->postJson('/api/v1/attendance/requests', [
            'request_type' => 'izin',
            'start_at' => '2026-12-01 07:00:00',
            'end_at' => '2026-12-01 15:00:00',
            'reason' => 'Menghadiri seminar MGMP Guru',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.request_status', 'pending');

        $this->assertDatabaseHas('employee_attendance_requests', [
            'employee_id' => $this->employee->id,
            'request_type' => 'izin',
            'request_status' => 'pending',
        ]);
    }

    public function test_teacher_seeder_runs_successfully(): void
    {
        $this->seed(\Database\Seeders\TeacherSeeder::class);

        $this->assertDatabaseHas('users', [
            'email' => 'guru.aktif@sekolah.sch.id',
        ]);
        $this->assertDatabaseHas('employees', [
            'employee_number' => 'EMP-198505122010011002',
        ]);
        $this->assertDatabaseHas('work_schedules', [
            'name' => 'Jadwal Reguler Guru',
        ]);
    }

    public function test_seeded_teacher_can_login_and_perform_attendance(): void
    {
        $this->seed(\Database\Seeders\TeacherSeeder::class);

        // 1. Login
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'guru.aktif@sekolah.sch.id',
            'password' => 'password123',
        ]);

        $loginRes->assertStatus(200);
        $token = $loginRes->json('access_token');
        $this->assertNotEmpty($token);

        // 2. Today Status
        Carbon::setTestNow('2026-11-26 07:05:00');
        $todayRes = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/attendance/today');

        $todayRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 3. Check-in within 1km
        $checkInRes = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'device_identifier' => 'Device-Testing',
            ]);

        $checkInRes->assertStatus(201)
            ->assertJsonPath('success', true);
    }
}
