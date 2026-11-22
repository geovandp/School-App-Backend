<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
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

        $user = User::with('profile.role')
            ->where('email', $data['email'])
            ->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
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
            $lockedUser = User::with('profile.role')
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

            $profile = $lockedUser->profile;
            $role = $profile?->role;

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
                    'role_id' => $profile?->role_id,
                    'role_name' => $role?->name,
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
                    'message' => 'User tidak ditemukan',
                ], 401);
            }

            $user->load([
                'profile.role',
            ]);
            $profile = $user->profile;
            $role = $profile?->role;
            $isStudent = $role?->code === 'student';
            $studentData = null;

            if ($isStudent && $profile) {
                $student = $profile->student()
                    ->with([
                        'activeEnrollment.classroom.gradeLevel',
                        'activeEnrollment.classroom.major',
                        'activeEnrollment.classroom.academicYear',
                    ])
                    ->first();

                if ($student) {
                    $enrollment = $student->activeEnrollment;
                    $classroom = $enrollment?->classroom;
                    $studentData = [
                        'id' => $student->id,
                        'nisn' => $student->nisn,
                        'nis' => $student->nis,
                        'admission_year' => $student->admission_year,
                        'enrollment' => $enrollment ? [
                            'id' => $enrollment->id,
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
            }
            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diambil',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role_id' => $profile?->role_id,
                    'role_name' => $role?->name,
                    'status' => $user->status,
                    'profile' => $profile ? [
                        'id' => $profile->id,
                        'full_name' => $profile->full_name,
                        'preferred_name' => $profile->preferred_name,
                        'gender' => $profile->gender,
                        'birth_place' => $profile->birth_place,
                        'birth_date' => $profile->birth_date,
                        'address' => $profile->address,
                        'phone' => $profile->phone,
                        'avatar_path' => $profile->avatar_path,
                    ] : null,
                    'student' => $studentData,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ], 200);
    }

    public function checkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Data email tidak ditemukan.',
            ], 404);
        }
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Silakan hubungi administrator.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil diverifikasi. Silakan ubah password Anda.',
        ], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang dimasukkan tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan.',
            ], 404);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak aktif. Tidak dapat mengubah password.',
            ], 403);
        }

        $user->password = Hash::make($request->password);
        $user->save();
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui. Silakan login kembali.',
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:current_password'
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Data yang dimasukkan tidak valid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama yang Anda masukkan salah.',
            ], 422);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ], 200);
    }
}
