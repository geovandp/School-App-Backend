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

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')->withTimestamps();
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'id');
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