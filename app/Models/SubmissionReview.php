<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionReview extends Model
{
    use HasFactory;

    protected $table = 'submission_reviews';

    protected $fillable = [
        'submission_id',
        'reviewed_by',
        'score',
        'feedback',
        'reviewed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'reviewed_by');
    }
}
