<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        try {
            $profiles = UserProfile::with([
                'role',
                'student',
                'teacher',
                'user:id,profile_id,email,status',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diambil.',
                'data' => $profiles,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'role_id' => [
                    'required',
                    'integer',
                    'exists:roles,id',
                ],
            ], [
                'role_id.required' => 'Role wajib dipilih.',
                'role_id.exists' => 'Role yang dipilih tidak ditemukan.',
            ]);

            $role = Role::findOrFail($request->role_id);
            $rules = [
                'full_name' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'preferred_name' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'gender' => [
                    'nullable',
                    Rule::in(['Male', 'Female']),
                ],
                'birth_place' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'birth_date' => [
                    'nullable',
                    'date',
                ],
                'address' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
                'phone' => [
                    'nullable',
                    'string',
                    'max:14',
                ],
                'avatar_path' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ];

            // Validasi berdasarkan role
            if ($role->code === 'student') {
                $rules = array_merge($rules, [
                    'nisn' => [
                        'required',
                        'string',
                        'size:10',
                        'unique:students,nisn',
                    ],
                    'nis' => [
                        'required',
                        'string',
                        'size:10',
                        'unique:students,nis',
                    ],
                    'admission_year' => [
                        'required',
                        'integer',
                        'digits:4',
                    ],
                ]);

            } elseif ($role->code === 'teacher') {
                $rules = array_merge($rules, [
                    'nip' => [
                        'required',
                        'string',
                        'max:18',
                        'unique:teachers,nip',
                    ],
                    'nuptk' => [
                        'required',
                        'string',
                        'max:16',
                        'unique:teachers,nuptk',
                    ],
                    'employment_status' => [
                        'required',
                        Rule::in([
                            'permanent',
                            'contract',
                            'honorary',
                        ]),
                    ],
                    'hire_date' => [
                        'required',
                        'date',
                    ],
                ]);
            }

            // Melakukan validasi seluruh data
            $validated = $request->validate($rules, [
                // student validation
                'full_name.required' => 'Nama lengkap wajib diisi.',
                'nisn.required' => 'NISN wajib diisi.',
                'nisn.size' => 'NISN harus terdiri dari 10 karakter.',
                'nisn.unique' => 'NISN sudah digunakan.',
                'nis.required' => 'NIS wajib diisi.',
                'nis.size' => 'NIS harus terdiri dari 10 karakter.',
                'nis.unique' => 'NIS sudah digunakan.',
                'admission_year.required' => 'Tahun masuk wajib diisi.',
                'admission_year.digits' => 'Tahun masuk harus terdiri dari 4 digit.',

                // teacher validation
                'nip.required' => 'NIP wajib diisi.',
                'nip.unique' => 'NIP sudah digunakan.',
                'nip.max' => 'NIP maksimal 18 karakter.',
                'nuptk.required' => 'NUPTK wajib diisi.',
                'nuptk.unique' => 'NUPTK sudah digunakan.',
                'nuptk.max' => 'NUPTK maksimal 16 karakter.',
                'employment_status.required' => 'Status kepegawaian wajib dipilih.',
                'employment_status.in' => 'Status kepegawaian tidak valid.',
                'hire_date.required' => 'Tanggal mulai bekerja wajib diisi.',
                'hire_date.date' => 'Format tanggal mulai bekerja tidak valid.',
            ]);

            $profile = DB::transaction(function () use ($validated, $role) {
                $profile = UserProfile::create([
                    'role_id' => $role->id,
                    'full_name' => $validated['full_name'],
                    'preferred_name' => $validated['preferred_name'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'birth_place' => $validated['birth_place'] ?? null,
                    'birth_date' => $validated['birth_date'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'avatar_path' => $validated['avatar_path'] ?? null,
                ]);

                if ($role->code === 'student') {
                    $profile->student()->create([
                        'nisn' => $validated['nisn'],
                        'nis' => $validated['nis'],
                        'admission_year' => $validated['admission_year'],
                    ]);

                }
                elseif ($role->code === 'teacher') {
                    $profile->teacher()->create([
                        'nip' => $validated['nip'],
                        'nuptk' => $validated['nuptk'],
                        'employment_status' => $validated['employment_status'],
                        'hire_date' => $validated['hire_date'],
                    ]);
                }
                return $profile;
            });

            $profile->load([
                'role',
                'student',
                'teacher',
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil ditambahkan.',
                'data' => $profile,
            ], 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambahkan data user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $profile = UserProfile::with([
                'role',
                // Data murid
                'user:id,profile_id,email,status',
                'student.activeEnrollment.classroom.gradeLevel',
                'student.activeEnrollment.classroom.major',
                'student.activeEnrollment.classroom.academicYear',

                // Data guru
                'teacher',
            ])->find($id);

            if (!$profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data user tidak ditemukan.',
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Detail data user berhasil diambil.',
                'data' => $profile,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil detail data user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'role_id' => [
                'required',
                'integer',
                'exists:roles,id',
            ],
        ], [
            'role_id.required' => 'Role wajib dipilih.',
            'role_id.exists' => 'Role yang dipilih tidak ditemukan.',
        ]);

        $profile = UserProfile::findOrFail($id);
        $role = Role::findOrFail($request->role_id);
        $student = $profile->student;
        $teacher = $profile->teacher;

        // Validasi data umum
        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'preferred_name' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'birth_place' => ['nullable', 'string', 'max:50'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:14'],
            'avatar_path' => ['nullable', 'string', 'max:255'],
        ];

        // Validasi tambahan jika role yang dipilih adalah student
        if ($role->code === 'student') {
            $rules = array_merge($rules, [
                'nisn' => [
                    'required',
                    'string',
                    'size:10',
                    Rule::unique('students', 'nisn')->ignore($student?->id),
                ],
                'nis' => [
                    'required',
                    'string',
                    'size:10',
                    Rule::unique('students', 'nis')->ignore($student?->id),
                ],
                'admission_year' => [
                    'required',
                    'integer',
                    'digits:4',
                ],
            ]);
        }

        // Validasi tambahan jika role yang dipilih adalah teacher
        elseif ($role->code === 'teacher') {
            $rules = array_merge($rules, [
                'nip' => [
                    'required',
                    'string',
                    'max:18',
                    Rule::unique('teachers', 'nip')->ignore($teacher?->id),
                ],
                'nuptk' => [
                    'required',
                    'string',
                    'max:16',
                    Rule::unique('teachers', 'nuptk')->ignore($teacher?->id),
                ],
                'employment_status' => [
                    'required',
                    Rule::in(['permanent', 'contract', 'honorary']),
                ],
                'hire_date' => [
                    'required',
                    'date',
                ],
            ]);
        }

        // Jalankan validasi
        $validated = $request->validate($rules, [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'nisn.required' => 'NISN wajib diisi.',
            'nisn.size' => 'NISN harus terdiri dari 10 karakter.',
            'nisn.unique' => 'NISN sudah digunakan.',
            'nis.required' => 'NIS wajib diisi.',
            'nis.size' => 'NIS harus terdiri dari 10 karakter.',
            'nis.unique' => 'NIS sudah digunakan.',
            'admission_year.required' => 'Tahun masuk wajib diisi.',
            'admission_year.digits' => 'Tahun masuk harus terdiri dari 4 digit.',
            'nip.required' => 'NIP wajib diisi.',
            'nip.unique' => 'NIP sudah digunakan.',
            'nip.max' => 'NIP maksimal 18 karakter.',
            'nuptk.required' => 'NUPTK wajib diisi.',
            'nuptk.unique' => 'NUPTK sudah digunakan.',
            'nuptk.max' => 'NUPTK maksimal 16 karakter.',
            'employment_status.required' => 'Status kepegawaian wajib dipilih.',
            'employment_status.in' => 'Status kepegawaian tidak valid.',
            'hire_date.required' => 'Tanggal mulai bekerja wajib diisi.',
            'hire_date.date' => 'Format tanggal mulai bekerja tidak valid.',
        ]);

        try {
            DB::transaction(function () use ($profile, $role, $validated) {
                $profile->update([
                    'role_id' => $role->id,
                    'full_name' => $validated['full_name'],
                    'preferred_name' => $validated['preferred_name'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'birth_place' => $validated['birth_place'] ?? null,
                    'birth_date' => $validated['birth_date'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'avatar_path' => $validated['avatar_path'] ?? null,
                ]);

                if ($role->code === 'student') {
                    $profile->student()->updateOrCreate(
                        [],
                        [
                            'nisn' => $validated['nisn'],
                            'nis' => $validated['nis'],
                            'admission_year' => $validated['admission_year'],
                        ]
                    );
                }
                elseif ($role->code === 'teacher') {
                    $profile->teacher()->updateOrCreate(
                        [],
                        [
                            'nip' => $validated['nip'],
                            'nuptk' => $validated['nuptk'],
                            'employment_status' => $validated['employment_status'],
                            'hire_date' => $validated['hire_date'],
                        ]
                    );
                }
            });

            // Ambil kembali data lengkap setelah diperbarui
            $profile->load([
                'role',
                'user:id,profile_id,email,status',
                'student.activeEnrollment.classroom.gradeLevel',
                'student.activeEnrollment.classroom.major',
                'student.activeEnrollment.classroom.academicYear',
                'teacher',
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diperbarui.',
                'data' => $profile,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui data user.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}