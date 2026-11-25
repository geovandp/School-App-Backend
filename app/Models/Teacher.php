<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $table = 'teachers';

    protected $fillable = [
        'profile_id',
        'employee_id',
        'nip',
        'nuptk',
        'employment_status',
        'hire_date',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'profile_id', 'id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function teachingAssignments(): HasMany
    {
        return $this->hasMany(TeachingAssignment::class, 'teacher_id');
    }

    public function teacherSkills(): HasMany
    {
        return $this->hasMany(TeacherSkill::class, 'teacher_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'created_by');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SubmissionReview::class, 'reviewed_by');
    }
}
