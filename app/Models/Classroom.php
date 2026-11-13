<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'grade_level_id',
        'academic_year_id',
        'major_id',
    ];

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function enrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
