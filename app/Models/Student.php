<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $fillable = [
        'nisn',
        'admission_year',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class, 'student_id', 'user_id');
    }
}
