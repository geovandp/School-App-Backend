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
    
    // public function role()
    // {
    //     return $this->belongsTo(Role::class, 'role_id', 'id');
    // }


    // public function student()
    // {
    //     return $this->hasOne(Student::class, 'profile_id', 'id');
    // }

    // public function teacher()
    // {
    //     return $this->hasOne(Teacher::class, 'profile_id', 'id');
    // }
}