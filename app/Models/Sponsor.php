<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sponsor extends Model
{
    use HasFactory, LogsActivityTrait;

    public const TYPES = ['person' => 'Person', 'organisation' => 'Organisation'];

    protected $guarded = [];

    public function sponsorships(): HasMany
    {
        return $this->hasMany(Sponsorship::class);
    }

    /**
     * Sponsorships still running (no end date, or ending in the future).
     */
    public function activeSponsorships(): HasMany
    {
        return $this->sponsorships()->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()->toDateString()));
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }
}
