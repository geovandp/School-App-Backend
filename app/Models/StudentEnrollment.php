<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    use HasFactory;

    protected $table = 'student_enrollments';

    protected $fillable = [
        'student_id',
        'classroom_id',
        'enrollment_date',
        'status',
        'exit_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'enrollment_date' => 'date',
            'exit_date' => 'date',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'user_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id', 'id');
    }
}
