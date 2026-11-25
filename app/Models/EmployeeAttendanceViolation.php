<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendanceViolation extends Model
{
    use HasFactory;

    protected $table = 'employee_attendance_violations';

    // Violation Types
    public const TYPE_LATE = 'late';
    public const TYPE_EARLY_LEAVE = 'early_leave';
    public const TYPE_UNAUTHORIZED_TEMPORARY_OUT = 'unauthorized_temporary_out';
    public const TYPE_UNAUTHORIZED_ABSENCE = 'unauthorized_absence';
    public const TYPE_MISSING_CHECK_OUT = 'missing_check_out';

    // Review Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_EXCUSED = 'excused';
    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'attendance_session_id',
        'violation_type',
        'detected_at',
        'start_at',
        'end_at',
        'duration_minutes',
        'description',
        'evidence_path',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'resolution_note',
    ];

    protected function casts(): array
    {
        return [
            'detected_at' => 'datetime',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendanceSession::class, 'attendance_session_id', 'id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'id');
    }
}
