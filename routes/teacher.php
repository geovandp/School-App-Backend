<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Teacher API Routes — /api/v1/teacher
|--------------------------------------------------------------------------
|
| Semua route di file ini otomatis dilindungi oleh dua middleware:
|   1. auth:sanctum  — memastikan user sudah terautentikasi
|   2. teacher       — memastikan user memiliki role 'teacher' dan aktif
|
*/

Route::middleware(['auth:sanctum', 'teacher'])->prefix('teacher')->name('teacher.')->group(function () {

    // -------------------------------------------------------------------------
    // A. Dashboard
    // -------------------------------------------------------------------------
    Route::get('/dashboard', [\App\Http\Controllers\Teacher\DashboardController::class, 'index'])
        ->name('dashboard');

    // -------------------------------------------------------------------------
    // B. Teaching Assignments & Classrooms
    // -------------------------------------------------------------------------
    Route::get('/teaching-assignments', [\App\Http\Controllers\Teacher\TeachingAssignmentController::class, 'index'])
        ->name('teaching-assignments.index');
    Route::get('/teaching-assignments/{teachingAssignment}', [\App\Http\Controllers\Teacher\TeachingAssignmentController::class, 'show'])
        ->name('teaching-assignments.show');
    Route::get('/classrooms', [\App\Http\Controllers\Teacher\TeachingAssignmentController::class, 'classrooms'])
        ->name('classrooms.index');
    Route::get('/classrooms/{classroom}/students', [\App\Http\Controllers\Teacher\TeachingAssignmentController::class, 'classroomStudents'])
        ->name('classrooms.students');

    // -------------------------------------------------------------------------
    // C. Schedules
    // -------------------------------------------------------------------------
    Route::get('/schedules', [\App\Http\Controllers\Teacher\ScheduleController::class, 'index'])
        ->name('schedules.index');
    Route::get('/schedules/today', [\App\Http\Controllers\Teacher\ScheduleController::class, 'today'])
        ->name('schedules.today');

    // -------------------------------------------------------------------------
    // D. Skills
    // -------------------------------------------------------------------------
    Route::get('/skills', [\App\Http\Controllers\Teacher\SkillController::class, 'index'])
        ->name('skills.index');
    Route::post('/skills', [\App\Http\Controllers\Teacher\SkillController::class, 'store'])
        ->name('skills.store');
    Route::put('/skills/{teacherSkill}', [\App\Http\Controllers\Teacher\SkillController::class, 'update'])
        ->name('skills.update');
    Route::delete('/skills/{teacherSkill}', [\App\Http\Controllers\Teacher\SkillController::class, 'destroy'])
        ->name('skills.destroy');

    // -------------------------------------------------------------------------
    // E. Learning Journals
    // -------------------------------------------------------------------------
    Route::get('/learning-journals', [\App\Http\Controllers\Teacher\LearningJournalController::class, 'index'])
        ->name('learning-journals.index');
    Route::post('/learning-journals', [\App\Http\Controllers\Teacher\LearningJournalController::class, 'store'])
        ->name('learning-journals.store');
    Route::get('/learning-journals/{learningJournal}', [\App\Http\Controllers\Teacher\LearningJournalController::class, 'show'])
        ->name('learning-journals.show');
    Route::put('/learning-journals/{learningJournal}', [\App\Http\Controllers\Teacher\LearningJournalController::class, 'update'])
        ->name('learning-journals.update');
    Route::delete('/learning-journals/{learningJournal}', [\App\Http\Controllers\Teacher\LearningJournalController::class, 'destroy'])
        ->name('learning-journals.destroy');
    Route::post('/learning-journals/{learningJournal}/attachments', [\App\Http\Controllers\Teacher\LearningJournalController::class, 'storeAttachment'])
        ->name('learning-journals.attachments.store');
    Route::delete('/learning-journals/{learningJournal}/attachments/{attachment}', [\App\Http\Controllers\Teacher\LearningJournalController::class, 'destroyAttachment'])
        ->name('learning-journals.attachments.destroy');
    Route::post('/learning-journals/{learningJournal}/submit', [\App\Http\Controllers\Teacher\LearningJournalController::class, 'submit'])
        ->name('learning-journals.submit');

    // -------------------------------------------------------------------------
    // F. Assignments
    // -------------------------------------------------------------------------
    Route::get('/assignments', [\App\Http\Controllers\Teacher\AssignmentController::class, 'index'])
        ->name('assignments.index');
    Route::post('/assignments', [\App\Http\Controllers\Teacher\AssignmentController::class, 'store'])
        ->name('assignments.store');
    Route::get('/assignments/{assignment}', [\App\Http\Controllers\Teacher\AssignmentController::class, 'show'])
        ->name('assignments.show');
    Route::put('/assignments/{assignment}', [\App\Http\Controllers\Teacher\AssignmentController::class, 'update'])
        ->name('assignments.update');
    Route::delete('/assignments/{assignment}', [\App\Http\Controllers\Teacher\AssignmentController::class, 'destroy'])
        ->name('assignments.destroy');
    Route::post('/assignments/{assignment}/attachments', [\App\Http\Controllers\Teacher\AssignmentController::class, 'storeAttachment'])
        ->name('assignments.attachments.store');
    Route::delete('/assignments/{assignment}/attachments/{attachment}', [\App\Http\Controllers\Teacher\AssignmentController::class, 'destroyAttachment'])
        ->name('assignments.attachments.destroy');
    Route::post('/assignments/{assignment}/publish', [\App\Http\Controllers\Teacher\AssignmentController::class, 'publish'])
        ->name('assignments.publish');
    Route::post('/assignments/{assignment}/close', [\App\Http\Controllers\Teacher\AssignmentController::class, 'close'])
        ->name('assignments.close');
    Route::get('/assignments/{assignment}/recipients', [\App\Http\Controllers\Teacher\AssignmentController::class, 'recipients'])
        ->name('assignments.recipients');
    Route::get('/assignments/{assignment}/submissions', [\App\Http\Controllers\Teacher\AssignmentController::class, 'submissions'])
        ->name('assignments.submissions');
    Route::get('/assignments/{assignment}/statistics', [\App\Http\Controllers\Teacher\AssignmentController::class, 'statistics'])
        ->name('assignments.statistics');

    // -------------------------------------------------------------------------
    // G. Submissions & Reviews
    // -------------------------------------------------------------------------
    Route::get('/submissions/{submission}', [\App\Http\Controllers\Teacher\SubmissionController::class, 'show'])
        ->name('submissions.show');
    Route::post('/submissions/{submission}/reviews', [\App\Http\Controllers\Teacher\SubmissionController::class, 'storeReview'])
        ->name('submissions.reviews.store');
    Route::get('/submissions/{submission}/reviews', [\App\Http\Controllers\Teacher\SubmissionController::class, 'reviews'])
        ->name('submissions.reviews.index');
    Route::put('/submission-reviews/{submissionReview}', [\App\Http\Controllers\Teacher\SubmissionController::class, 'updateReview'])
        ->name('submission-reviews.update');
});
