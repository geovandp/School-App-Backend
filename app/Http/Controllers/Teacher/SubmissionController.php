<?php

namespace App\Http\Controllers\Teacher;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissionController extends TeacherController
{
    public function show(Request $request, $submission): JsonResponse
    {
        // TODO: Phase 3 — Modul 7
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function storeReview(Request $request, $submission): JsonResponse
    {
        // TODO: Phase 3 — Modul 7
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function updateReview(Request $request, $submissionReview): JsonResponse
    {
        // TODO: Phase 3 — Modul 7
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function reviews(Request $request, $submission): JsonResponse
    {
        // TODO: Phase 3 — Modul 7
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }
}
