<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'exit_date' => 'date',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function currentEnrollment(): HasOne
    {
        return $this->hasOne(Enrollment::class)->latestOfMany();
    }

    public function studentPrograms(): HasMany
    {
        return $this->hasMany(StudentProgram::class);
    }

    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class, 'student_programs')
            ->withPivot(['start_date', 'end_date'])
            ->withTimestamps();
    }

    public function results(): HasMany
    {
        return $this->hasMany(AcademicResult::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function consent(): HasOne
    {
        return $this->hasOne(Consent::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    public function mentorAssignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class);
    }

    public function mentorshipSessions(): HasMany
    {
        return $this->hasMany(MentorshipSession::class);
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }

    public function careerInterests(): HasMany
    {
        return $this->hasMany(CareerInterest::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function alumniOutcomes(): HasMany
    {
        return $this->hasMany(AlumniOutcome::class);
    }

    public function sponsorships(): HasMany
    {
        return $this->hasMany(Sponsorship::class);
    }
}
