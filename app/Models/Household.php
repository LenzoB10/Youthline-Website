<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Household extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function homeVisits(): HasMany
    {
        return $this->hasMany(HomeVisit::class);
    }

    public function repairProjects(): HasMany
    {
        return $this->hasMany(RepairProject::class);
    }

    public function latestVisit(): HasOne
    {
        return $this->hasOne(HomeVisit::class)->latestOfMany('visit_date');
    }

    /**
     * Visit photos need a household student whose guardian allowed photos.
     */
    public function photoConsentGiven(): bool
    {
        return Consent::query()
            ->whereIn('student_id', $this->students()->select('id'))
            ->where('photo_allowed', true)
            ->exists();
    }
}
