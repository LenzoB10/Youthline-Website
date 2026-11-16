<?php

namespace App\Models;

use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distribution extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'given_on' => 'date',
            'quantity' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Money given. Cash records have an amount; older item records are valued at
     * quantity × the item's unit cost (0 if no cost was set).
     */
    public function value(): float
    {
        if ($this->amount !== null) {
            return (float) $this->amount;
        }

        return $this->quantity * (float) ($this->itemCategory?->unit_cost ?? 0);
    }

    /**
     * "MWK 20,000.00" for cash, "6 Exercise books" for older item records.
     */
    public function describe(string $currency): string
    {
        return $this->amount !== null
            ? $currency.' '.number_format((float) $this->amount, 2)
            : $this->quantity.' '.($this->itemCategory?->name ?? 'items');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function itemCategory(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
