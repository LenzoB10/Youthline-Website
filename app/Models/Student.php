<?php

namespace App\Models;

use App\Enums\StudentStatus;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Student extends Model
{
    use HasFactory, LogsActivityTrait;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'exit_date' => 'date',
            'status' => StudentStatus::class,
        ];
    }

    /**
     * Search by student name, guardian name or either phone number.
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        // "Grace Banda" should match first + last name; MySQL/MariaDB (production) and
        // SQLite (development) join strings differently.
        $fullName = in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? "CONCAT(first_name, ' ', last_name)"
            : "first_name || ' ' || last_name";

        return $query->where(function (Builder $q) use ($term, $fullName) {
            $q->whereRaw("{$fullName} like ?", ["%{$term}%"])
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('guardian_name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")
                ->orWhere('guardian_phone', 'like', "%{$term}%");
        });
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

    /**
     * Programs the student is in now. Leaving a program sets end_date — the row is kept.
     */
    public function activePrograms(): BelongsToMany
    {
        return $this->programs()->wherePivotNull('end_date');
    }

    /**
     * The enrolment for a given academic year (one row per student per year).
     */
    public function enrollmentFor(?AcademicYear $year): ?Enrollment
    {
        return $year ? $this->enrollments()->where('academic_year_id', $year->id)->first() : null;
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

    public function currentMentorAssignment(): HasOne
    {
        return $this->hasOne(MentorAssignment::class)
            ->ofMany(['start_date' => 'max', 'id' => 'max'], fn ($query) => $query->whereNull('end_date'));
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
