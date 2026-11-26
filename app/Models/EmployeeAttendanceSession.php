<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAttendanceSession extends Model
{
    use HasFactory;

    protected $table = 'employee_attendance_sessions';

    // Session States
    public const STATE_OPEN = 'open';
    public const STATE_CLOSED = 'closed';
    public const STATE_CANCELLED = 'cancelled';

    // Attendance Statuses
    public const STATUS_HADIR = 'H';
    public const STATUS_TERLAMBAT = 'T';
    public const STATUS_ALPHA = 'A';
    public const STATUS_TAP = 'TAP';
    public const STATUS_TAPT = 'TAPT';
    public const STATUS_IZIN_PULANG = 'IP';
    public const STATUS_PULANG_CEPAT = 'PC';
    public const STATUS_IZIN = 'I';
    public const STATUS_SAKIT = 'S';
    public const STATUS_DINAS_LUAR = 'DL';
    public const STATUS_CUTI = 'CT';

    protected $fillable = [
        'employee_id',
        'employee_work_schedule_id',
        'school_calendar_day_id',
        'attendance_date',
        'session_state',
        'attendance_status',
        'snapshot_start_time',
        'snapshot_end_time',
        'first_check_in_at',
        'last_check_out_at',
        'late_minutes',
        'early_leave_minutes',
        'total_work_minutes',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'first_check_in_at' => 'datetime',
            'last_check_out_at' => 'datetime',
            'finalized_at' => 'datetime',
            'late_minutes' => 'integer',
            'early_leave_minutes' => 'integer',
            'total_work_minutes' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function employeeWorkSchedule(): BelongsTo
    {
        return $this->belongsTo(EmployeeWorkSchedule::class, 'employee_work_schedule_id', 'id');
    }

    public function schoolCalendarDay(): BelongsTo
    {
        return $this->belongsTo(SchoolCalendarDay::class, 'school_calendar_day_id', 'id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceEvent::class, 'attendance_session_id', 'id');
    }

    public function requests(): BelongsToMany
    {
        return $this->belongsToMany(
            EmployeeAttendanceRequest::class,
            'employee_attendance_session_requests',
            'attendance_session_id',
            'attendance_request_id'
        )->withTimestamps();
    }

    public function violations(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceViolation::class, 'attendance_session_id', 'id');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceStatusHistory::class, 'attendance_session_id', 'id')->orderBy('changed_at');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceAdjustment::class, 'attendance_session_id', 'id');
    }

    public function isOpen(): bool
    {
        return $this->session_state === self::STATE_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->session_state === self::STATE_CLOSED;
    }

    public function isFinalized(): bool
    {
        return !is_null($this->finalized_at);
    }
}
