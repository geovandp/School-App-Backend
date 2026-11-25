<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'employee_number',
        'employment_status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'employee_id', 'id');
    }

    public function employeeWorkSchedules(): HasMany
    {
        return $this->hasMany(EmployeeWorkSchedule::class, 'employee_id', 'id');
    }

    public function workSchedules(): BelongsToMany
    {
        return $this->belongsToMany(WorkSchedule::class, 'employee_work_schedules', 'employee_id', 'work_schedule_id')
            ->withPivot(['id', 'effective_from', 'effective_until'])
            ->withTimestamps();
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceSession::class, 'employee_id', 'id');
    }

    public function attendanceRequests(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceRequest::class, 'employee_id', 'id');
    }

    /**
     * Dapatkan work schedule aktif untuk tanggal tertentu.
     * Satu employee hanya memiliki satu work schedule aktif pada satu tanggal kerja.
     */
    public function getActiveWorkSchedule(string|Carbon $date = null): ?EmployeeWorkSchedule
    {
        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        return $this->employeeWorkSchedules()
            ->with('workSchedule.days')
            ->where('effective_from', '<=', $targetDate)
            ->where(function ($query) use ($targetDate) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $targetDate);
            })
            ->latest('effective_from')
            ->first();
    }
}
