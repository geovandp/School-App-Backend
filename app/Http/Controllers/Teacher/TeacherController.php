<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

/**
 * Base controller untuk semua Teacher controllers.
 *
 * Menyediakan akses ke ApiResponse trait dan helper
 * untuk mendapatkan data teacher dari user yang terautentikasi.
 */
abstract class TeacherController extends Controller
{
    use ApiResponse;

    /**
     * Ambil model Teacher dari user yang sedang login.
     * Sudah dipastikan ada oleh middleware EnsureUserIsTeacher.
     */
    protected function getTeacher(\Illuminate\Http\Request $request): \App\Models\Teacher
    {
        return $request->user()->teacher;
    }
}
