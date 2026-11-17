<?php

namespace App\Http\Controllers\Teacher;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends TeacherController
{
    public function index(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 3
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function today(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 3
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }
}
