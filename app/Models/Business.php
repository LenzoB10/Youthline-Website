<?php

namespace App\Models;

use App\Enums\FundingType;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'amount_given' => 'decimal:2',
            'funding_type' => FundingType::class,
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

    public function repaidTotal(): float
    {
        return (float) ($this->repaid_total ?? $this->loanRepayments()->sum('amount'));
    }

    /**
     * What is still to be repaid on a loan (0 for grants).
     */
    public function loanBalance(): float
    {
        if ($this->funding_type !== FundingType::Loan) {
            return 0.0;
        }

        return round((float) $this->amount_given - $this->repaidTotal(), 2);
    }
}
