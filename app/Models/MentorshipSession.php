<?php

namespace App\Models;

use App\Enums\SessionType;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorshipSession extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    /**
     * notes are restricted: hidden from serialization by default.
     * MentorshipService::canReadNotes() decides who may see them.
     */
    protected $hidden = [
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'type' => SessionType::class,
            'follow_up_needed' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
