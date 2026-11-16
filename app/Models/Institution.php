<?php

namespace App\Models;

use App\Enums\InstitutionType;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => InstitutionType::class,
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    /**
     * Deleting a used institution would blank it out of enrolment and fee history.
     */
    public function isInUse(): bool
    {
        return $this->enrollments()->exists() || $this->fees()->exists();
    }
}
