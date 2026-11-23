<?php

namespace App\Models;

use App\Enums\FamilySupportType;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Support already given to a family: money, food, a home repair, and so on.
 */
class FamilySupport extends Model
{
    use LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'given_on' => 'date',
            'type' => FamilySupportType::class,
            'amount' => 'decimal:2',
        ];
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(Photo::class, 'photoable');
    }
}
