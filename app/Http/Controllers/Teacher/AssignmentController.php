<?php

namespace App\Http\Controllers\Teacher;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssignmentController extends TeacherController
{
    public function index(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function store(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function show(Request $request, $assignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function update(Request $request, $assignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function destroy(Request $request, $assignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function storeAttachment(Request $request, $assignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function destroyAttachment(Request $request, $assignment, $attachment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function publish(Request $request, $assignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function close(Request $request, $assignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function recipients(Request $request, $assignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function submissions(Request $request, $assignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function statistics(Request $request, $assignment): JsonResponse
    {
        // TODO: Phase 3 — Modul 6
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }
}
