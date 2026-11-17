<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Resources\Teacher\ClassroomResource;
use App\Http\Resources\Teacher\ClassroomStudentResource;
use App\Http\Resources\Teacher\TeachingAssignmentResource;
use App\Models\Classroom;
use App\Models\StudentEnrollment;
use App\Models\TeachingAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeachingAssignmentController extends TeacherController
{
    /**
     * Menampilkan daftar penugasan mengajar milik guru yang login.
     * GET /api/v1/teacher/teaching-assignments
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $this->getTeacher($request);

        if (! $teacher) {
            return $this->notFoundResponse('Data profil guru tidak ditemukan.');
        }

        $query = TeachingAssignment::query()
            ->where('teacher_id', $teacher->id)
            ->with([
                'classroom.gradeLevel',
                'classroom.academicYear',
                'classroom.major',
                'course',
            ]);

        // Filter status (active/inactive)
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        // Filter academic_year_id melalui classroom
        if ($request->filled('academic_year_id')) {
            $query->whereHas('classroom', function ($q) use ($request) {
                $q->where('academic_year_id', $request->query('academic_year_id'));
            });
        }

        // Filter course_id
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->query('course_id'));
        }

        $perPage = (int) $request->query('per_page', 15);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar penugasan mengajar berhasil diambil',
            'data'    => TeachingAssignmentResource::collection($paginator->items()),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Menampilkan detail satu penugasan mengajar.
     * GET /api/v1/teacher/teaching-assignments/{id}
     */
    public function show(Request $request, $id): JsonResponse
    {
        $teacher = $this->getTeacher($request);

        if (! $teacher) {
            return $this->notFoundResponse('Data profil guru tidak ditemukan.');
        }

        $assignment = TeachingAssignment::with([
            'classroom.gradeLevel',
            'classroom.academicYear',
            'classroom.major',
            'course',
            'schedules.room',
            'schedules.startPeriod',
            'schedules.endPeriod',
        ])->find($id);

        if (! $assignment) {
            return $this->notFoundResponse('Penugasan mengajar tidak ditemukan.');
        }

        // Authorization check: pastikan penugasan ini milik guru yang login
        if ($assignment->teacher_id !== $teacher->id) {
            return $this->forbiddenResponse('Anda tidak memiliki izin untuk melihat data penugasan mengajar ini.');
        }

        return $this->successResponse(
            new TeachingAssignmentResource($assignment),
            'Detail penugasan mengajar berhasil diambil'
        );
    }

    /**
     * Menampilkan daftar kelas (rombongan belajar) yang diajar oleh guru.
     * GET /api/v1/teacher/classrooms
     */
    public function classrooms(Request $request): JsonResponse
    {
        $teacher = $this->getTeacher($request);

        if (! $teacher) {
            return $this->notFoundResponse('Data profil guru tidak ditemukan.');
        }

        $query = Classroom::query()
            ->whereHas('teachingAssignments', function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id);
            })
            ->with([
                'gradeLevel',
                'academicYear',
                'major',
                'teachingAssignments' => function ($q) use ($teacher) {
                    $q->where('teacher_id', $teacher->id)->with('course');
                },
            ])
            ->withCount([
                'enrollments as active_students_count' => function ($q) {
                    $q->where('status', 'active');
                },
            ]);

        // Filter academic_year_id
        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->query('academic_year_id'));
        }

        $classrooms = $query->orderBy('name')->get();

        return $this->successResponse(
            ClassroomResource::collection($classrooms),
            'Daftar kelas berhasil diambil'
        );
    }

    /**
     * Menampilkan daftar siswa pada kelas tertentu yang diajar oleh guru.
     * GET /api/v1/teacher/classrooms/{id}/students
     */
    public function classroomStudents(Request $request, $id): JsonResponse
    {
        $teacher = $this->getTeacher($request);

        if (! $teacher) {
            return $this->notFoundResponse('Data profil guru tidak ditemukan.');
        }

        $classroom = Classroom::find($id);

        if (! $classroom) {
            return $this->notFoundResponse('Kelas tidak ditemukan.');
        }

        // Authorization: Pastikan guru terdaftar mengajar di kelas ini
        $isTeachingInClass = TeachingAssignment::query()
            ->where('classroom_id', $classroom->id)
            ->where('teacher_id', $teacher->id)
            ->exists();

        if (! $isTeachingInClass) {
            return $this->forbiddenResponse('Akses ditolak. Anda tidak mengajar di kelas ini.');
        }

        $query = StudentEnrollment::query()
            ->where('classroom_id', $classroom->id)
            ->with(['student.user.profile']);

        // Filter status enrollment (default: active jika tidak diisi)
        $status = $request->query('status', 'active');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Search nama siswa atau NISN/NIS
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->whereHas('student', function ($sq) use ($search) {
                $sq->where('nisn', 'like', "%{$search}%")
                    ->orWhere('nis', 'like', "%{$search}%")
                    ->orWhereHas('user.profile', function ($pq) use ($search) {
                        $pq->where('full_name', 'like', "%{$search}%")
                            ->orWhere('preferred_name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar siswa kelas berhasil diambil',
            'data'    => ClassroomStudentResource::collection($paginator->items()),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ]);
    }
}
