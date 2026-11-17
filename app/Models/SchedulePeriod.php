<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchedulePeriod extends Model
{
    use HasFactory;

    protected $table = 'schedule_periods';

    protected $fillable = [
        'academic_year_id',
        'period_number',
        'name',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'period_number' => 'integer',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }

    public function startingSchedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class, 'start_period_id');
    }

    public function endingSchedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class, 'end_period_id');
    }
}
