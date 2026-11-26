<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id',
        'profile_id',
        'nisn',
        'nis',
        'admission_year',
    ];

    protected function casts(): array
    {
        return [
            'admission_year' => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function profile()
    {
        return $this->belongsTo(UserProfile::class, 'profile_id', 'id');
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id', 'id');
    }

    public function activeEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class, 'student_id', 'id')->where('status', 'active');
    }
}
