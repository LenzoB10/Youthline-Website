<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /**
     * Published testimonials in display order.
     */
    public function scopeForDisplay(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('display_order')->orderBy('id');
    }
}
