<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'user_profiles';
    
    protected $primaryKey = 'id';

    protected $fillable = [
        'role_id',
        'full_name',
        'preferred_name',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'phone',
        'avatar_path',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class, 'profile_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function student()
    {
        return $this->hasOne(Student::class, 'profile_id', 'id');
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'profile_id', 'id');
    }
}
