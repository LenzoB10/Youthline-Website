<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mentor extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MentorshipSession::class);
    }

    /**
     * Students currently assigned (assignment not ended).
     */
    public function currentAssignments(): HasMany
    {
        return $this->assignments()->whereNull('end_date');
    }

    public function isInUse(): bool
    {
        return $this->assignments()->exists() || $this->sessions()->exists();
    }
}
