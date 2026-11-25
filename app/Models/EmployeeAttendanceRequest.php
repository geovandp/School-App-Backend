<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAttendanceRequest extends Model
{
    use HasFactory;

    protected $table = 'employee_attendance_requests';

    // Request Types
    public const TYPE_IZIN = 'izin';
    public const TYPE_SAKIT = 'sakit';
    public const TYPE_CUTI = 'cuti';
    public const TYPE_DATANG_TERLAMBAT = 'datang_terlambat';
    public const TYPE_KELUAR_SEMENTARA = 'keluar_sementara';
    public const TYPE_PULANG_AWAL = 'pulang_awal';
    public const TYPE_DINAS_LUAR = 'dinas_luar';

    // Request Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'employee_id',
        'request_type',
        'start_at',
        'end_at',
        'reason',
        'attachment_path',
        'request_status',
        'request_at',
        'approved_by',
        'approved_at',
        'approval_note',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'request_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function sessions(): BelongsToMany
    {
        return $this->belongsToMany(
            EmployeeAttendanceSession::class,
            'employee_attendance_session_requests',
            'attendance_request_id',
            'attendance_session_id'
        )->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceEvent::class, 'attendance_request_id', 'id');
    }

    public function isPending(): bool
    {
        return $this->request_status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->request_status === self::STATUS_APPROVED;
    }
}
