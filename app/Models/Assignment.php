<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    use HasFactory;

    protected $table = 'assignments';

    protected $fillable = [
        'teaching_assignment_id',
        'created_by',
        'title',
        'description',
        'assignment_type',
        'target_type',
        'published_at',
        'start_at',
        'due_at',
        'max_score',
        'allow_late_submission',
        'max_attempts',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'start_at' => 'datetime',
            'due_at' => 'datetime',
            'max_score' => 'decimal:2',
            'allow_late_submission' => 'boolean',
            'max_attempts' => 'integer',
        ];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AssignmentAttachment::class, 'assignment_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AssignmentRecipient::class, 'assignment_id');
    }
}
