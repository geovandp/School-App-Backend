<?php

namespace App\Http\Controllers\Teacher;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeachingAssignmentController extends TeacherController
{
    public function index(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 2
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function show(Request $request, $teachingAssignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 2
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function classrooms(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 2
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function classroomStudents(Request $request, $classroom): JsonResponse
    {
        // TODO: Phase 3 — Modul 2
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }
}
