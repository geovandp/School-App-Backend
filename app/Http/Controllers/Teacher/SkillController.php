<?php

namespace App\Http\Controllers\Teacher;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends TeacherController
{
    public function index(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 4
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function store(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 4
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function update(Request $request, $teacherSkill): JsonResponse
    {
        // TODO: Phase 3 — Modul 4
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function destroy(Request $request, $teacherSkill): JsonResponse
    {
        // TODO: Phase 3 — Modul 4
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }
}
