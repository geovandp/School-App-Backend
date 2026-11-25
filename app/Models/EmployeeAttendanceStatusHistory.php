<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAttendanceStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'employee_attendance_status_histories';

    protected $fillable = [
        'attendance_session_id',
        'old_status',
        'new_status',
        'reason',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EmployeeAttendanceSession::class, 'attendance_session_id', 'id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by', 'id');
    }
}
