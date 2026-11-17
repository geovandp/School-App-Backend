<?php

namespace Tests\Feature\Teacher;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\GradeLevel;
use App\Models\Major;
use App\Models\Role;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Teacher;
use App\Models\TeachingAssignment;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeachingAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $teacherUser;
    private Teacher $teacher;
    private Classroom $classroom1;
    private Classroom $classroom2;
    private Course $course1;
    private TeachingAssignment $assignment1;

    protected function setUp(): void
    {
        parent::setUp();

        $teacherRole = Role::create(['nama' => 'teacher']);
        $studentRole = Role::create(['nama' => 'student']);

        // Teacher 1 (current login)
        $this->teacherUser = User::factory()->create([
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        UserProfile::create([
            'user_id' => $this->teacherUser->id,
            'full_name' => 'Guru Pertama',
        ]);
        $this->teacher = Teacher::create([
            'user_id' => $this->teacherUser->id,
            'nip' => '111122223333444455',
        ]);

        // Academic Master
        $academicYear = AcademicYear::create([
            'name' => '2026/2027',
            'start_date' => '2026-07-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
        ]);
        $gradeLevel = GradeLevel::create(['level_number' => 10]);
        $major = Major::create(['name' => 'RPL']);

        $this->classroom1 = Classroom::create([
            'grade_level_id' => $gradeLevel->id,
            'academic_year_id' => $academicYear->id,
            'major_id' => $major->id,
            'name' => 'X RPL 1',
            'status' => true,
        ]);

        $this->classroom2 = Classroom::create([
            'grade_level_id' => $gradeLevel->id,
            'academic_year_id' => $academicYear->id,
            'major_id' => $major->id,
            'name' => 'X RPL 2',
            'status' => true,
        ]);

        $this->course1 = Course::create([
            'code' => 'PROG-1',
            'name' => 'Pemrograman Dasar',
            'status' => true,
        ]);

        // Teaching Assignment untuk Teacher 1 di Classroom 1
        $this->assignment1 = TeachingAssignment::create([
            'classroom_id' => $this->classroom1->id,
            'teacher_id' => $this->teacher->id,
            'course_id' => $this->course1->id,
            'status' => 'active',
        ]);

        // Siapkan Siswa di Classroom 1
        $studentUser = User::factory()->create([
            'role_id' => $studentRole->id,
            'status' => 'active',
        ]);
        UserProfile::create([
            'user_id' => $studentUser->id,
            'full_name' => 'Budi Siswa',
        ]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'nisn' => '1234567890',
            'nis' => '12345',
        ]);
        StudentEnrollment::create([
            'student_id' => $student->user_id,
            'classroom_id' => $this->classroom1->id,
            'enrollment_date' => '2026-07-01',
            'status' => 'active',
        ]);
    }

    public function test_teacher_can_view_teaching_assignments_list(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson('/api/v1/teacher/teaching-assignments');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Daftar penugasan mengajar berhasil diambil',
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->assignment1->id)
            ->assertJsonPath('data.0.classroom.name', 'X RPL 1')
            ->assertJsonPath('data.0.course.code', 'PROG-1');
    }

    public function test_teacher_can_view_teaching_assignment_detail(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson("/api/v1/teacher/teaching-assignments/{$this->assignment1->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->assignment1->id,
                    'classroom' => [
                        'id' => $this->classroom1->id,
                        'name' => 'X RPL 1',
                    ],
                    'course' => [
                        'code' => 'PROG-1',
                        'name' => 'Pemrograman Dasar',
                    ],
                ],
            ]);
    }

    public function test_teacher_cannot_view_other_teachers_assignment(): void
    {
        // Teacher 2
        $teacherRole = Role::firstWhere('nama', 'teacher');
        $otherTeacherUser = User::factory()->create([
            'role_id' => $teacherRole->id,
            'status' => 'active',
        ]);
        $otherTeacher = Teacher::create([
            'user_id' => $otherTeacherUser->id,
            'nip' => '999988887777666655',
        ]);

        $otherAssignment = TeachingAssignment::create([
            'classroom_id' => $this->classroom2->id,
            'teacher_id' => $otherTeacher->id,
            'course_id' => $this->course1->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson("/api/v1/teacher/teaching-assignments/{$otherAssignment->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk melihat data penugasan mengajar ini.',
            ]);
    }

    public function test_teacher_can_view_assigned_classrooms(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson('/api/v1/teacher/classrooms');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $this->classroom1->id)
            ->assertJsonPath('data.0.active_students_count', 1);
    }

    public function test_teacher_can_view_students_in_assigned_classroom(): void
    {
        Sanctum::actingAs($this->teacherUser);

        $response = $this->getJson("/api/v1/teacher/classrooms/{$this->classroom1->id}/students");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Budi Siswa')
            ->assertJsonPath('data.0.nisn', '1234567890');
    }

    public function test_teacher_cannot_view_students_in_unassigned_classroom(): void
    {
        Sanctum::actingAs($this->teacherUser);

        // Teacher 1 tidak mengajar di Classroom 2
        $response = $this->getJson("/api/v1/teacher/classrooms/{$this->classroom2->id}/students");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak mengajar di kelas ini.',
            ]);
    }
}
