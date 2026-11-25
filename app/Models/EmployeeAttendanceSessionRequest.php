<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class EmployeeAttendanceSessionRequest extends Pivot
{
    use HasFactory;

    protected $table = 'employee_attendance_session_requests';

    public $incrementing = true;

    protected $fillable = [
        'attendance_session_id',
        'attendance_request_id',
    ];

    public function session()
    {
        return $this->belongsTo(EmployeeAttendanceSession::class, 'attendance_session_id', 'id');
    }

    public function request()
    {
        return $this->belongsTo(EmployeeAttendanceRequest::class, 'attendance_request_id', 'id');
    }
}
