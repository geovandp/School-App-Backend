<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendanceAdjustment extends Model
{
    use HasFactory;

    protected $table = 'employee_attendance_adjustments';

    // Adjustment Types
    public const TYPE_ADD_MISSING_EVENT = 'add_missing_event';
    public const TYPE_CORRECT_EVENT = 'correct_event';
    public const TYPE_CANCEL_INVALID_EVENT = 'cancel_invalid_event';
    public const TYPE_STATUS_OVERRIDE = 'status_override';

    // Approval Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'attendance_session_id',
        'original_event_id',
        'created_event_id',
        'adjustment_type',
        'corrected_event_type',
        'corrected_event_at',
        'reason',
        'evidence_path',
        'requested_by',
        'approved_by',
        'approval_status',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'corrected_event_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendanceSession::class, 'attendance_session_id', 'id');
    }

    public function originalEvent(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendanceEvent::class, 'original_event_id', 'id');
    }

    public function createdEvent(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendanceEvent::class, 'created_event_id', 'id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by', 'id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}
