<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningJournalAttachment extends Model
{
    use HasFactory;

    protected $table = 'learning_journal_attachments';

    protected $fillable = [
        'learning_journal_id',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public function learningJournal(): BelongsTo
    {
        return $this->belongsTo(LearningJournal::class, 'learning_journal_id');
    }
}
