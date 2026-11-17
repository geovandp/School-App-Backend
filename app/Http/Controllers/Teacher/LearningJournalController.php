<?php

namespace App\Http\Controllers\Teacher;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningJournalController extends TeacherController
{
    public function index(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 5
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function store(Request $request): JsonResponse
    {
        // TODO: Phase 3 — Modul 5
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function show(Request $request, $learningJournal): JsonResponse
    {
        // TODO: Phase 3 — Modul 5
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function update(Request $request, $learningJournal): JsonResponse
    {
        // TODO: Phase 3 — Modul 5
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function destroy(Request $request, $learningJournal): JsonResponse
    {
        // TODO: Phase 3 — Modul 5
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function storeAttachment(Request $request, $learningJournal): JsonResponse
    {
        // TODO: Phase 3 — Modul 5
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function destroyAttachment(Request $request, $learningJournal, $attachment): JsonResponse
    {
        // TODO: Phase 3 — Modul 5
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }

    public function submit(Request $request, $learningJournal): JsonResponse
    {
        // TODO: Phase 3 — Modul 5
        return $this->successResponse(null, 'Endpoint belum diimplementasikan.');
    }
}
