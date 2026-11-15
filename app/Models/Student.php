<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
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

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id', 'user_id');
    }

    public function activeEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class, 'student_id', 'user_id')->where('status', 'active');
    }
}
