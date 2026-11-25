<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkSchedule extends Model
{
    use HasFactory;

    protected $table = 'work_schedules';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'check_in_open_minutes',
        'late_tolerance_minutes',
        'evaluation_after_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'check_in_open_minutes' => 'integer',
            'late_tolerance_minutes' => 'integer',
            'evaluation_after_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class, 'work_schedule_id', 'id');
    }

    public function employeeWorkSchedules(): HasMany
    {
        return $this->hasMany(EmployeeWorkSchedule::class, 'work_schedule_id', 'id');
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_work_schedules', 'work_schedule_id', 'employee_id')
            ->withPivot(['id', 'effective_from', 'effective_until'])
            ->withTimestamps();
    }
}
