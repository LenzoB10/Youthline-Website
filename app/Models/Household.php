<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A family Youthline supports. It may be a student's home, or a community
 * family with no student on the programme at all.
 */
class Household extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'members_count' => 'integer',
            'is_supported' => 'boolean',
            'photo_consent' => 'boolean',
            'photo_consent_on' => 'date',
        ];
    }

    /**
     * Families currently being supported (they get visit reminders).
     */
    public function scopeSupported(Builder $query): Builder
    {
        return $query->where('is_supported', true);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function homeVisits(): HasMany
    {
        return $this->hasMany(HomeVisit::class);
    }

    public function familySupports(): HasMany
    {
        return $this->hasMany(FamilySupport::class);
    }

    public function latestVisit(): HasOne
    {
        return $this->hasOne(HomeVisit::class)->latestOfMany('visit_date');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function displayName(): string
    {
        return $this->head_name ?: 'Unnamed family';
    }

    /**
     * Photos of the home are allowed when the family itself consented, or (for a
     * student's family) a guardian gave photo consent on the student's record.
     */
    public function photoConsentGiven(): bool
    {
        if ($this->photo_consent) {
            return true;
        }

        return Consent::query()
            ->whereIn('student_id', $this->students()->select('id'))
            ->where('photo_allowed', true)
            ->exists();
    }
}
