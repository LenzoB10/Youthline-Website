<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'amount_given' => 'decimal:2',
            'is_running' => 'boolean',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(BusinessCheckin::class);
    }

    public function loanRepayments(): HasMany
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function loanBalance(): float
    {
        if ($this->funding_type !== 'loan') {
            return 0.0;
        }

        return (float) $this->amount_given - (float) $this->loanRepayments()->sum('amount');
    }
}
