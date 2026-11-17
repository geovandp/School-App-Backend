<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingAssignment extends Model
{
    use HasFactory;

    protected $table = 'teaching_assignments';

    protected $fillable = [
        'classroom_id',
        'teacher_id',
        'course_id',
        'status',
    ];

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class, 'teaching_assignment_id');
    }

    public function journals(): HasMany
    {
        return $this->hasMany(LearningJournal::class, 'teaching_assignment_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'teaching_assignment_id');
    }
}
