<?php

namespace App\Services\Academics;

use App\Enums\DocumentType;
use App\Enums\ResultType;
use App\Models\AcademicResult;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Services\Students\StudentDocumentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ResultService
{
    public function __construct(
        protected StudentDocumentService $documents,
        protected GradeTrendService $trends,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated AcademicResultRequest data
     */
    public function record(Student $student, array $data, ?UploadedFile $reportCard, User $by): AcademicResult
    {
        $result = DB::transaction(function () use ($student, $data, $reportCard, $by) {
            $result = AcademicResult::create([
                ...$this->attributes($student, $data),
                'student_id' => $student->id,
                'report_card_document_id' => $reportCard
                    ? $this->documents->store($student, $reportCard, DocumentType::ReportCard, $by)->id
                    : null,
                'recorded_by' => $by->id,
            ]);
            $this->syncGrades($result, $data['grades'] ?? []);

            return $result;
        });

        $this->trends->alertIfDropped($result);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(AcademicResult $result, array $data, ?UploadedFile $reportCard, User $by): AcademicResult
    {
        $wasDrop = $this->isCurrentlyDrop($result);

        DB::transaction(function () use ($result, $data, $reportCard, $by) {
            $attributes = $this->attributes($result->student, $data);

            // A new scan is added to the student's documents; the old one stays there too.
            if ($reportCard) {
                $attributes['report_card_document_id'] = $this->documents
                    ->store($result->student, $reportCard, DocumentType::ReportCard, $by)->id;
            }

            $result->update($attributes);
            $this->syncGrades($result, $data['grades'] ?? []);
        });

        // Only alert when a correction turns this result INTO a drop (no repeat alerts).
        if (! $wasDrop) {
            $this->trends->alertIfDropped($result->refresh());
        }

        return $result;
    }

    /**
     * The report card stays in the student's documents.
     */
    public function delete(AcademicResult $result): void
    {
        $result->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function attributes(Student $student, array $data): array
    {
        $type = ResultType::from($data['exam_type']);
        $term = Term::query()->findOrFail($data['term_id']);

        return [
            'term_id' => $term->id,
            'exam_type' => $type,
            // Results hang off that year's enrolment so the level/school is known.
            'enrollment_id' => Enrollment::query()
                ->where('student_id', $student->id)
                ->where('academic_year_id', $term->academic_year_id)
                ->value('id'),
            'average' => $type->usesAverage() ? $data['average'] : null,
            'msce_points' => $type === ResultType::Msce ? $data['msce_points'] : null,
            'position' => $data['position'] ?? null,
            'remarks' => $data['remarks'] ?? null,
        ];
    }

    /**
     * @param  array<int, array{subject?: ?string, grade?: ?string}>  $grades
     */
    protected function syncGrades(AcademicResult $result, array $grades): void
    {
        $result->subjectGrades()->delete();

        $rows = collect($grades)
            ->filter(fn ($row) => filled($row['subject'] ?? null))
            ->map(fn ($row) => ['subject' => trim($row['subject']), 'grade' => filled($row['grade'] ?? null) ? trim($row['grade']) : null])
            ->unique('subject')
            ->values()
            ->all();

        if ($rows) {
            $result->subjectGrades()->createMany($rows);
        }
    }

    protected function isCurrentlyDrop(AcademicResult $result): bool
    {
        $previous = $this->trends->previous($result->loadMissing('term'));

        return $previous && $this->trends->isDrop((float) $result->average - (float) $previous->average);
    }
}
