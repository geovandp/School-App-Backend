<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     *
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }
        if (!Hash::check($data['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }
        if ($user->status !== 'active') {
            $messages = [
                'pending' => 'Akun masih menunggu persetujuan admin.',
                'suspended' => 'Akun sedang ditangguhkan. Silakan hubungi admin.',
                'inactive' => 'Akun telah dinonaktifkan. Silakan hubungi admin.',
            ];
            return response()->json([
                'success' => false,
                'message' => $messages[$user->status]
                    ?? 'Status akun tidak valid.',
                'status' => $user->status,
            ], 403);
        }

        $tokenName = 'access_token';
        $expiration = config('sanctum.expiration');

        return DB::transaction(function () use (
            $user,
            $tokenName,
            $expiration
        ) {
            $lockedUser = User::with('role')
                ->where('id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $tokens = $lockedUser->tokens()->get();

            $hasActiveToken = false;

            foreach ($tokens as $existingToken) {
                $withinGlobalExpiration = is_null($expiration)
                    || $existingToken->created_at->gt(
                        now()->subMinutes((int) $expiration)
                    );

                $withinTokenExpiration = is_null($existingToken->expires_at)
                    || $existingToken->expires_at->isFuture();

                $isValid = $withinGlobalExpiration
                    && $withinTokenExpiration;

                if ($isValid) {
                    $hasActiveToken = true;
                } else {
                    $existingToken->delete();
                }
            }

            if ($hasActiveToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun sedang digunakan pada perangkat lain. Silakan logout dari perangkat sebelumnya atau tunggu hingga sesi berakhir.',
                    'error_code' => 'ACCOUNT_ALREADY_LOGGED_IN',
                ], 409);
            }

            $token = $lockedUser->createToken(
                $tokenName,
                ['access']
            )->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil.',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => $expiration
                    ? $expiration * 60
                    : null,
                'user' => [
                    'id' => $lockedUser->id,
                    'email' => $lockedUser->email,
                    'role_id' => $lockedUser->role_id,
                    'role_name' => $lockedUser->role?->nama,
                    'status' => $lockedUser->status,
                ],
            ], 200);
        });
    }


    public function me(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User tidak ditemukan'
                ], 401);
            }

            // Mengambil informasi akun, profil, dan data akademik
            $user->load([
                'role',
                'profile',
            ]);

            // Jika role student, ambil informasi akademik
            if ($user->role?->nama === 'student') {
                $user->load([
                    'student.activeEnrollment.classroom.gradeLevel',
                    'student.activeEnrollment.classroom.major',
                    'student.activeEnrollment.classroom.academicYear',
                ]);
            }

            // Menyiapkan informasi student
            $studentData = null;

            if ($user->role?->nama === 'student' && $user->student) {

                $student = $user->student;
                $enrollment = $student->activeEnrollment;
                $classroom = $enrollment?->classroom;

                $studentData = [
                    'nisn' => $student->nisn,
                    'admission_year' => $student->admission_year,
                    'enrollment' => $enrollment ? [
                        'enrollment_date' => $enrollment->enrollment_date,
                        'status' => $enrollment->status,
                        'exit_date' => $enrollment->exit_date,
                        'notes' => $enrollment->notes,
                    ] : null,
                    'classroom' => $classroom ? [
                        'id' => $classroom->id,
                        'name' => $classroom->name,
                        'status' => $classroom->status,
                        'grade_level' => $classroom->gradeLevel ? [
                            'id' => $classroom->gradeLevel->id,
                            'level_number' => $classroom->gradeLevel->level_number,
                        ] : null,
                        'major' => $classroom->major ? [
                            'id' => $classroom->major->id,
                            'name' => $classroom->major->name,
                        ] : null,
                        'academic_year' => $classroom->academicYear ? [
                            'id' => $classroom->academicYear->id,
                            'name' => $classroom->academicYear->name,
                            'start_date' => $classroom->academicYear->start_date,
                            'end_date' => $classroom->academicYear->end_date,
                            'is_active' => $classroom->academicYear->is_active,
                        ] : null,
                    ] : null,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diambil',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'role_name' => $user->role?->nama,
                    'status' => $user->status,
                    'profile' => $user->profile ? [
                        'id' => $user->profile->id,
                        'full_name' => $user->profile->full_name,
                        'preferred_name' => $user->profile->preferred_name,
                        'gender' => $user->profile->gender,
                        'birth_place' => $user->profile->birth_place,
                        'birth_date' => $user->profile->birth_date,
                        'address' => $user->profile->address,
                        'phone' => $user->profile->phone,
                        'avatar_path' => $user->profile->avatar_path,
                    ] : null,
                    'student' => $studentData,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // public function me(Request $request)
    // {
    //     try {
    //         $user = $request->user();
            
    //         if (!$user) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'User tidak ditemukan'
    //             ], 401);
    //         }

    //         $user->load([
    //             'role',
    //             'profile'
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Data user berhasil diambil',
    //             'user' => [
    //                 'id' => $user->id,
    //                 'email' => $user->email,
    //                 'role_id' => $user->role_id,
    //                 'role_name' => $user->role->nama,
    //                 'status' => $user->status,
    //                 'profile' => $user->profile ? [
    //                     'id' => $user->profile->id,
    //                     'full_name' => $user->profile->full_name,
    //                     'preferred_name' => $user->profile->preferred_name,
    //                     'gender' => $user->profile->gender,
    //                     'birth_place' => $user->profile->birth_place,
    //                     'birth_date' => $user->profile->birth_date,
    //                     'address' => $user->profile->address,
    //                     'phone' => $user->profile->phone,
    //                     'avatar_path' => $user->profile->avatar_path,
    //                 ] : null,
    //                 'created_at' => $user->created_at,
    //                 'updated_at' => $user->updated_at,
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Terjadi kesalahan server',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ], 200);
    }
}