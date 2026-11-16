<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'uploaded_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    /**
     * A signed consent form can't be removed while it is the evidence for a
     * consent that still allows a photo or story to be published.
     */
    public function isActiveConsentEvidence(): bool
    {
        return Consent::query()
            ->where('document_id', $this->id)
            ->where(fn ($q) => $q->where('photo_allowed', true)->orWhere('story_allowed', true))
            ->exists();
    }

    /**
     * A receipt behind a payment, or a report card behind a result, is part of the
     * financial / academic record and stays while that record exists.
     */
    public function isRecordEvidence(): bool
    {
        return Payment::query()->where('receipt_document_id', $this->id)->exists()
            || AcademicResult::query()->where('report_card_document_id', $this->id)->exists();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function consent(): HasOne
    {
        return $this->hasOne(Consent::class);
    }
}
