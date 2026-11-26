<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $fillable = [
        'profile_id',
        'role_id',
        'email',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile()
    {
        return $this->belongsTo(UserProfile::class, 'profile_id', 'id');
    }

    public function userProfile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function getProfileAttribute()
    {
        return $this->getRelationValue('profile') 
            ?? $this->getRelationValue('userProfile')
            ?? UserProfile::where('user_id', $this->id)->first();
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')->withTimestamps();
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
    }

    public function getEmployeeAttribute()
    {
        if ($this->relationLoaded('employee')) {
            return $this->getRelationValue('employee');
        }

        $employee = Employee::where('user_id', $this->id)->first();
        if ($employee) {
            return $employee;
        }

        // Query langsung tanpa melalui $this->teacher untuk menghindari circular dependency
        $teacher = Teacher::where('user_id', $this->id)
            ->when($this->profile_id, fn($q) => $q->orWhere('profile_id', $this->profile_id))
            ->first();

        if ($teacher) {
            if ($teacher->employee_id) {
                return Employee::find($teacher->employee_id);
            }

            $employee = Employee::create([
                'user_id' => $this->id,
                'employee_number' => $teacher->nip ?? 'EMP-' . str_pad((string)$this->id, 5, '0', STR_PAD_LEFT),
                'employment_status' => $teacher->employment_status ?? 'permanent',
            ]);
            $teacher->update(['employee_id' => $employee->id]);
            return $employee;
        }

        return null;
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'user_id', 'id');
    }

    public function getTeacherAttribute()
    {
        if ($this->relationLoaded('teacher')) {
            return $this->getRelationValue('teacher');
        }

        // Query langsung tanpa memanggil $this->employee untuk menghindari circular dependency
        return Teacher::where('user_id', $this->id)
            ->when($this->profile_id, fn($q) => $q->orWhere('profile_id', $this->profile_id))
            ->first();
    }

    public function hasRole(string $roleCode): bool
    {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('code', $roleCode);
        }

        // Fallback backward-compatible check via profile role if role_user not populated
        if ($this->relationLoaded('profile') && $this->profile?->relationLoaded('role')) {
            if ($this->profile?->role?->code === $roleCode) {
                return true;
            }
        }

        return $this->roles()->where('code', $roleCode)->exists();
    }

    public function hasAnyRole(array $roleCodes): bool
    {
        foreach ($roleCodes as $roleCode) {
            if ($this->hasRole($roleCode)) {
                return true;
            }
        }

        return false;
    }

    public function isEmployee(): bool
    {
        return $this->employee !== null || $this->employee()->exists();
    }

    public function isTeacher(): bool
    {
        return $this->hasRole('teacher') || ($this->employee && $this->employee->teacher !== null);
    }
}