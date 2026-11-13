<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_date',
        'status',
        'exit_date',
        'notes',
        'student_id',
        'classroom_id',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'user_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }
}
