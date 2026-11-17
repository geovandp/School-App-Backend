<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningJournal extends Model
{
    use HasFactory;

    protected $table = 'learning_journals';

    protected $fillable = [
        'teaching_assignment_id',
        'created_by',
        'learning_date',
        'topic',
        'learning_objective',
        'learning_material',
        'learning_activities',
        'learning_outcomes',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'learning_date' => 'date',
        ];
    }

    public function teachingAssignment(): BelongsTo
    {
        return $this->belongsTo(TeachingAssignment::class, 'teaching_assignment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LearningJournalAttachment::class, 'learning_journal_id');
    }
}
