<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemCategory extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:2',
        ];
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    /**
     * Distributions cascade on delete, so a used category must never be removed.
     */
    public function isInUse(): bool
    {
        return $this->distributions()->exists();
    }
}
