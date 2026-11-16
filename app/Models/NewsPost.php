<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsPost extends Model
{
    use HasFactory;

    /**
     * Post categories and their public, plural labels.
     */
    public const CATEGORIES = [
        'news' => 'News',
        'success_story' => 'Success stories',
        'activity' => 'Activity',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Posts safe to show on the public site: published, and if linked to a
     * student, only when that student's guardian has allowed their story.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(function (Builder $inner): void {
                $inner->whereNull('student_id')
                    ->orWhereHas('student.consent', fn (Builder $consent) => $consent->where('story_allowed', true));
            });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
