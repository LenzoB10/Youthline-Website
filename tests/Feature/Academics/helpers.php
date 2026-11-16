<?php

use App\Enums\FeeApproval;
use App\Models\AcademicYear;
use App\Models\Fee;
use App\Models\Student;
use App\Models\Term;

require_once __DIR__.'/../Students/helpers.php';

function makeTerm(AcademicYear $year, string $name, string $start, bool $current = false): Term
{
    return Term::create([
        'academic_year_id' => $year->id,
        'name' => $name,
        'start_date' => $start,
        'end_date' => date('Y-m-d', strtotime($start.' +80 days')),
        'is_current' => $current,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function makeFee(Student $student, Term $term, float $share = 45000, array $attributes = []): Fee
{
    return Fee::create([
        'student_id' => $student->id,
        'term_id' => $term->id,
        'amount_owed' => $share,
        'youthline_share' => $share,
        'approval_status' => FeeApproval::Approved,
        ...$attributes,
    ]);
}
