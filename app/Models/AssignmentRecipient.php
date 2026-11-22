<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssignmentRecipient extends Model
{
    use HasFactory;

    protected $table = 'assignment_recipients';

    protected $fillable = [
        'assignment_id',
        'student_id',
        'assigned_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'assignment_recipient_id');
    }

    public function latestSubmission()
    {
        return $this->hasOne(Submission::class, 'assignment_recipient_id')->latestOfMany('attempt_number');
    }
}
