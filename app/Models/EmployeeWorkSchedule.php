<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeWorkSchedule extends Model
{
    use HasFactory;

    protected $table = 'employee_work_schedules';

    protected $fillable = [
        'employee_id',
        'work_schedule_id',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class, 'work_schedule_id', 'id');
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceSession::class, 'employee_work_schedule_id', 'id');
    }
}
