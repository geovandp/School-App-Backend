<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAttendanceEvent;
use App\Models\EmployeeAttendanceRequest;
use App\Models\EmployeeAttendanceSession;
use App\Models\EmployeeAttendanceViolation;
use App\Models\Role;
use App\Models\SchoolCalendarDay;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\WorkSchedule;
use App\Services\Attendance\AttendanceEventService;
use App\Services\Attendance\AttendanceRequestService;
use App\Services\Attendance\AttendanceSessionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Employee $employee;
    protected WorkSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat Role
        $role = Role::create(['code' => 'teacher', 'name' => 'Guru']);

        // Buat Profile
        $profile = UserProfile::create([
            'role_id' => $role->id,
            'full_name' => 'Budi Guru',
            'phone' => '08123456789',
        ]);

        // Buat User
        $this->user = User::create([
            'profile_id' => $profile->id,
            'email' => 'budi.guru@school.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $this->user->roles()->attach($role->id);

        // Buat Employee
        $this->employee = Employee::create([
            'user_id' => $this->user->id,
            'employee_number' => 'EMP-001',
            'employment_status' => 'permanent',
        ]);

        // Hubungkan Teacher ke Employee
        Teacher::create([
            'profile_id' => $profile->id,
            'employee_id' => $this->employee->id,
            'nip' => '198501012010011001',
        ]);

        // Buat Jadwal Kerja (07:00 - 15:00)
        $this->schedule = WorkSchedule::create([
            'name' => 'Jadwal Guru',
            'start_time' => '07:00:00',
            'end_time' => '15:00:00',
            'check_in_open_minutes' => 60,
            'late_tolerance_minutes' => 15,
            'evaluation_after_minutes' => 120,
            'is_active' => true,
        ]);

        // Hari kerja Senin-Minggu agar test selalu jalan
        for ($i = 1; $i <= 7; $i++) {
            $this->schedule->days()->create(['day_of_week' => $i]);
        }

        // Assign jadwal ke employee
        $this->employee->employeeWorkSchedules()->create([
            'work_schedule_id' => $this->schedule->id,
            'effective_from' => '2026-01-01',
        ]);
    }

    public function test_check_in_within_radius_success(): void
    {
        Carbon::setTestNow('2026-11-25 07:05:00'); // Jam masuk 07:00, toleransi 15 mnt -> Tepat waktu (H)

        // Koordinat sekolah: -6.2088, 106.8456
        $response = $this->actingAs($this->user)->postJson('/api/v1/attendance/check-in', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'device_identifier' => 'TestBrowser',
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('employee_attendance_events', [
            'event_type' => 'check_in',
        ]);

        $this->assertDatabaseHas('employee_attendance_sessions', [
            'employee_id' => $this->employee->id,
            'attendance_status' => 'H',
        ]);

        Carbon::setTestNow(); // Reset test now
    }

    public function test_check_in_late_is_marked_as_t(): void
    {
        Carbon::setTestNow('2026-11-25 07:45:00'); // Terlambat 45 menit -> T

        $response = $this->actingAs($this->user)->postJson('/api/v1/attendance/check-in', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('employee_attendance_sessions', [
            'employee_id' => $this->employee->id,
            'attendance_status' => 'T',
            'late_minutes' => 45,
        ]);

        // Cek pencatatan pelanggaran terlambat
        $this->assertDatabaseHas('employee_attendance_violations', [
            'violation_type' => 'late',
            'duration_minutes' => 45,
        ]);

        Carbon::setTestNow();
    }

    public function test_check_in_outside_radius_is_rejected(): void
    {
        // Koordinat 2 km dari sekolah
        $response = $this->actingAs($this->user)->postJson('/api/v1/attendance/check-in', [
            'latitude' => -6.1900,
            'longitude' => 106.8456,
            'device_identifier' => 'TestBrowser',
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['success' => false]);
    }

    public function test_cannot_check_in_twice(): void
    {
        $this->actingAs($this->user)->postJson('/api/v1/attendance/check-in', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ])->assertStatus(201);

        $response = $this->actingAs($this->user)->postJson('/api/v1/attendance/check-in', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ]);

        $response->assertStatus(422);
    }

    public function test_attendance_lifecycle_flow(): void
    {
        // 1. Check in (07:00)
        Carbon::setTestNow('2026-11-25 07:00:00');
        $this->actingAs($this->user)->postJson('/api/v1/attendance/check-in', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ])->assertStatus(201);

        // 2. Temporary Check out (10:00)
        Carbon::setTestNow('2026-11-25 10:00:00');
        $this->actingAs($this->user)->postJson('/api/v1/attendance/temporary-check-out', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'reason' => 'Tugas dinas sebentar ke kantor pos',
        ])->assertStatus(200);

        // 3. Return Check in (11:00)
        Carbon::setTestNow('2026-11-25 11:00:00');
        $this->actingAs($this->user)->postJson('/api/v1/attendance/return-check-in', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ])->assertStatus(200);

        // 4. Final Check out (15:05)
        Carbon::setTestNow('2026-11-25 15:05:00');
        $this->actingAs($this->user)->postJson('/api/v1/attendance/final-check-out', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ])->assertStatus(200);

        $session = EmployeeAttendanceSession::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($session->first_check_in_at);
        $this->assertNotNull($session->last_check_out_at);
        $this->assertEquals(4, $session->events()->count());
        $this->assertEquals(EmployeeAttendanceSession::STATE_CLOSED, $session->session_state);

        Carbon::setTestNow();
    }

    public function test_attendance_request_creation_and_approval(): void
    {
        // Ajukan izin
        $response = $this->actingAs($this->user)->postJson('/api/v1/attendance/requests', [
            'request_type' => 'izin',
            'start_at' => Carbon::today()->setTime(7, 0)->toDateTimeString(),
            'end_at' => Carbon::today()->setTime(15, 0)->toDateTimeString(),
            'reason' => 'Ada keperluan keluarga mendesak',
        ]);

        $response->assertStatus(201);
        $requestId = $response->json('data.id');

        // Buat Admin user
        $adminRole = Role::create(['code' => 'admin', 'name' => 'Administrator']);
        $adminProfile = UserProfile::create(['role_id' => $adminRole->id, 'full_name' => 'Admin SMS']);
        $adminUser = User::create([
            'profile_id' => $adminProfile->id,
            'email' => 'admin@school.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $adminUser->roles()->attach($adminRole->id);

        // Admin approve
        $approveResponse = $this->actingAs($adminUser)->postJson("/api/v1/admin/attendance-approvals/{$requestId}/approve", [
            'approval_note' => 'Disetujui',
        ]);

        $approveResponse->assertStatus(200);

        $this->assertDatabaseHas('employee_attendance_requests', [
            'id' => $requestId,
            'request_status' => 'approved',
        ]);

        // Pastikan status sesi ter-update menjadi 'I' (Izin)
        $this->assertDatabaseHas('employee_attendance_sessions', [
            'employee_id' => $this->employee->id,
            'attendance_status' => 'I',
        ]);
    }

    public function test_attendance_adjustment_request_and_approval(): void
    {
        // 1. Buat sesi dengan check-in
        Carbon::setTestNow('2026-11-25 07:00:00');
        $this->actingAs($this->user)->postJson('/api/v1/attendance/check-in', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ])->assertStatus(201);

        $session = EmployeeAttendanceSession::where('employee_id', $this->employee->id)->first();

        // 2. Guru lupa tap pulang, lalu mengajukan adjustment
        $response = $this->actingAs($this->user)->postJson('/api/v1/attendance/adjustments', [
            'attendance_session_id' => $session->id,
            'adjustment_type' => 'add_missing_event',
            'corrected_event_type' => 'final_check_out',
            'corrected_event_at' => '2026-11-25 15:00:00',
            'reason' => 'Perangkat smartphone kehabisan baterai saat jam pulang',
        ]);

        $response->assertStatus(201);
        $adjustmentId = $response->json('data.id');

        // 3. Admin approve adjustment
        $adminRole = Role::create(['code' => 'admin', 'name' => 'Administrator']);
        $adminProfile = UserProfile::create(['role_id' => $adminRole->id, 'full_name' => 'Admin SMS']);
        $adminUser = User::create([
            'profile_id' => $adminProfile->id,
            'email' => 'admin2@school.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $adminUser->roles()->attach($adminRole->id);

        $approveRes = $this->actingAs($adminUser)->postJson("/api/v1/admin/attendance-adjustments/{$adjustmentId}/approve");
        $approveRes->assertStatus(200);

        $this->assertDatabaseHas('employee_attendance_adjustments', [
            'id' => $adjustmentId,
            'approval_status' => 'approved',
        ]);

        // Pastikan last_check_out_at terisi dan sesi closed
        $session->refresh();
        $this->assertNotNull($session->last_check_out_at);
        $this->assertEquals(EmployeeAttendanceSession::STATE_CLOSED, $session->session_state);

        Carbon::setTestNow();
    }

    public function test_attendance_finalization_evaluates_alpha_and_tap(): void
    {
        // Employee 1: Tidak pernah check in -> harus jadi Alpha (A)
        $sessionService = app(AttendanceSessionService::class);
        $sessionService->getOrCreateTodaySession($this->employee, Carbon::parse('2026-11-25'));

        // Employee 2: Check in tepat waktu, tapi tidak check out -> harus jadi TAP
        $profile2 = UserProfile::create(['role_id' => Role::first()->id, 'full_name' => 'Siti Guru']);
        $user2 = User::create([
            'profile_id' => $profile2->id,
            'email' => 'siti@school.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $emp2 = Employee::create([
            'user_id' => $user2->id,
            'employee_number' => 'EMP-002',
            'employment_status' => 'permanent',
        ]);
        $emp2->employeeWorkSchedules()->create([
            'work_schedule_id' => $this->schedule->id,
            'effective_from' => '2026-01-01',
        ]);

        Carbon::setTestNow('2026-11-25 07:00:00');
        $this->actingAs($user2)->postJson('/api/v1/attendance/check-in', [
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ])->assertStatus(201);

        // Jalankan finalisasi presensi oleh Admin
        $adminRole = Role::create(['code' => 'admin', 'name' => 'Administrator']);
        $adminProfile = UserProfile::create(['role_id' => $adminRole->id, 'full_name' => 'Admin SMS']);
        $adminUser = User::create([
            'profile_id' => $adminProfile->id,
            'email' => 'admin3@school.test',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);
        $adminUser->roles()->attach($adminRole->id);

        Carbon::setTestNow('2026-11-25 18:00:00');
        $finalizeRes = $this->actingAs($adminUser)->postJson('/api/v1/admin/attendance/finalize', [
            'date' => '2026-11-25',
        ]);
        $finalizeRes->assertStatus(200);

        // Employee 1 -> Alpha
        $this->assertDatabaseHas('employee_attendance_sessions', [
            'employee_id' => $this->employee->id,
            'attendance_status' => 'A',
            'session_state' => 'closed',
        ]);

        // Employee 2 -> TAP
        $this->assertDatabaseHas('employee_attendance_sessions', [
            'employee_id' => $emp2->id,
            'attendance_status' => 'TAP',
            'session_state' => 'closed',
        ]);

        Carbon::setTestNow();
    }
}
