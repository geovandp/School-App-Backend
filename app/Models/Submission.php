<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Submission extends Model
{
    use HasFactory;

    protected $table = 'submissions';

    protected $fillable = [
        'assignment_recipient_id',
        'attempt_number',
        'answer_text',
        'submitted_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(AssignmentRecipient::class, 'assignment_recipient_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SubmissionAttachment::class, 'submission_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(SubmissionReview::class, 'submission_id');
    }

    public function latestReview(): HasOne
    {
        return $this->hasOne(SubmissionReview::class, 'submission_id')->latestOfMany();
    }
}
