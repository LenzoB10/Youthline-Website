<?php

namespace App\Notifications;

use App\Models\AcademicResult;
use App\Notifications\Concerns\InAppAlert;
use Illuminate\Notifications\Notification;

class GradeDropAlert extends Notification
{
    use InAppAlert;

    public function __construct(
        public AcademicResult $result,
        public AcademicResult $previous,
    ) {}

    protected function title(): string
    {
        return 'Grade drop: '.$this->result->student->full_name;
    }

    protected function message(): string
    {
        $from = (float) $this->previous->average;
        $to = (float) $this->result->average;

        return sprintf(
            '<strong>%s</strong>\'s average fell from %s%% (%s) to %s%% (%s), a drop of %s points.',
            e($this->result->student->full_name),
            e(rtrim(rtrim(number_format($from, 1), '0'), '.')),
            e($this->termLabel($this->previous)),
            e(rtrim(rtrim(number_format($to, 1), '0'), '.')),
            e($this->termLabel($this->result)),
            e(rtrim(rtrim(number_format($from - $to, 1), '0'), '.')),
        );
    }

    protected function url(): ?string
    {
        return route('students.show', $this->result->student_id).'#academics';
    }

    protected function kind(): string
    {
        return 'grade_drop';
    }

    protected function termLabel(AcademicResult $result): string
    {
        $term = $result->term;

        return trim(($term?->academicYear?->name ?? '').' '.($term?->name ?? ''));
    }
}
