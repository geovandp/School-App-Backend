<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolCalendarDay extends Model
{
    use HasFactory;

    protected $table = 'school_calendar_days';

    protected $fillable = [
        'calendar_date',
        'day_type',
        'is_working_day',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'calendar_date' => 'date',
            'is_working_day' => 'boolean',
        ];
    }

    public function attendanceSessions(): HasMany
    {
        return $this->hasMany(EmployeeAttendanceSession::class, 'school_calendar_day_id', 'id');
    }
}
