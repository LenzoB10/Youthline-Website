<?php

namespace App\Http\Controllers\Students;

use App\Enums\DocumentType;
use App\Enums\EducationLevel;
use App\Enums\ResultType;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DatatableRequest;
use App\Http\Requests\Students\DeleteStudentRequest;
use App\Http\Requests\Students\StudentRequest;
use App\Models\AcademicResult;
use App\Models\Document;
use App\Models\Enrollment;
use App\Models\ExitReason;
use App\Models\Fee;
use App\Models\Household;
use App\Models\Institution;
use App\Models\Payment;
use App\Models\Program;
use App\Models\Student;
use App\Models\Term;
use App\Services\Academics\GradeTrendService;
use App\Services\Settings\AcademicCalendarService;
use App\Services\Settings\SystemSettingsService;
use App\Services\Students\StudentService;
use App\Services\Support\SupportProfileService;
use App\Traits\HasDataTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

#[Middleware('permission:list students', only: ['index', 'show'])]
#[Middleware('permission:add student', only: ['create', 'store'])]
#[Middleware('permission:update student', only: ['edit', 'update'])]
#[Middleware('permission:delete student', only: ['destroy'])]
class StudentController extends Controller
{
    use HasDataTable;

    public function __construct(
        protected StudentService $students,
        protected AcademicCalendarService $calendar,
        protected GradeTrendService $trends,
        protected SystemSettingsService $settings,
        protected SupportProfileService $support,
    ) {}

    public function index(DatatableRequest $request): Response
    {
        $this->restrictSorting($request, ['last_name', 'status', 'created_at'], 'last_name');
        $yearId = $this->calendar->current()?->id;

        $students = $this->processDataTable(
            // Name search runs through Student::search (full name + guardian + phones),
            // so no extra searchable columns are passed to the trait.
            Student::query()->search($request->input('search'))->with('currentEnrollment.institution'),
            $request,
            [],
            [
                'status' => 'status',
                'level' => fn ($q, $level) => $q->whereHas('enrollments', fn ($e) => $e->where('academic_year_id', $yearId)->where('level', $level)),
                'program' => fn ($q, $programId) => $q->whereHas('studentPrograms', fn ($p) => $p->where('program_id', $programId)->whereNull('end_date')),
            ],
            defaultSortBy: 'last_name',
            defaultSortDirection: 'asc',
        );

        $students->getCollection()->transform(fn (Student $student) => [
            'id' => $student->id,
            'full_name' => $student->full_name,
            'guardian_name' => $student->guardian_name,
            'status' => $student->status->value,
            'status_label' => $student->status->label(),
            'level' => EducationLevel::tryFrom((string) $student->currentEnrollment?->level)?->label(),
            'institution' => $student->currentEnrollment?->institution?->name,
            'created_at' => $student->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Students/Index', [
            'students' => $students->appends($request->query()),
            'filters' => $request->query(),
            'statuses' => StudentStatus::options(),
            'levels' => EducationLevel::options(),
            'programs' => Program::query()->orderBy('display_order')->get(['id', 'name'])
                ->map(fn (Program $p) => ['value' => $p->id, 'label' => $p->name]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Students/Form', $this->formOptions());
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $student = $this->students->register($request->validated(), $request->user());

        return redirect()->route('students.show', $student)
            ->with('success', "{$student->full_name} was added as an applicant. Upload their documents next.");
    }

    public function show(Request $request, Student $student): Response
    {
        $student->load([
            'household.students', 'creator:id,name', 'consent',
            'enrollments.academicYear', 'enrollments.institution',
            'studentPrograms.program', 'documents.uploader:id,name',
            'results.term.academicYear', 'results.subjectGrades', 'results.recorder:id,name',
            'results.enrollment',
        ]);
        $fees = $student->fees()
            ->withPaymentTotals()
            ->with(['term.academicYear', 'institution', 'creator:id,name', 'approver:id,name',
                'payments' => fn ($q) => $q->with(['recorder:id,name', 'approver:id,name', 'errorMarker:id,name'])->oldest('paid_on')->oldest('id')])
            ->get()
            ->sortByDesc(fn (Fee $fee) => $fee->term?->start_date?->timestamp ?? 0)
            ->values();
        $settings = $this->settings->get();
        $currentYear = $this->calendar->current();

        return Inertia::render('Students/Show', [
            'student' => [
                ...$student->only([
                    'id', 'first_name', 'last_name', 'full_name', 'gender', 'phone', 'home_location',
                    'guardian_name', 'guardian_phone', 'guardian_relationship', 'exit_reason',
                ]),
                'date_of_birth' => $student->date_of_birth?->toDateString(),
                'age' => $student->date_of_birth?->age,
                'status' => $student->status->value,
                'status_label' => $student->status->label(),
                'exit_date' => $student->exit_date?->toDateString(),
                'created_at' => $student->created_at?->toIso8601String(),
                'created_by' => $student->creator?->name,
            ],
            'household' => $student->household ? [
                ...$student->household->only(['id', 'head_name', 'village_area', 'district', 'phone', 'members_count']),
                'siblings' => $student->household->students
                    ->reject(fn (Student $s) => $s->is($student))
                    ->map(fn (Student $s) => ['id' => $s->id, 'full_name' => $s->full_name, 'status_label' => $s->status->label()])
                    ->values(),
            ] : null,
            'programs' => $student->studentPrograms
                ->sortByDesc('start_date')
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'name' => $row->program?->name,
                    'start_date' => $row->start_date?->toDateString(),
                    'end_date' => $row->end_date?->toDateString(),
                ])->values(),
            'enrollments' => $student->enrollments
                ->sortByDesc(fn (Enrollment $e) => [$e->academicYear?->start_date?->timestamp ?? 0, $e->id])
                ->map(fn (Enrollment $e) => [
                    'id' => $e->id,
                    'year' => $e->academicYear?->name,
                    'is_current_year' => $currentYear && $e->academic_year_id === $currentYear->id,
                    'institution_id' => $e->institution_id,
                    'institution' => $e->institution?->name,
                    'level' => $e->level,
                    'level_label' => EducationLevel::tryFrom((string) $e->level)?->label() ?? $e->level,
                    'course' => $e->course,
                    'is_repeating' => $e->is_repeating,
                ])->values(),
            'documents' => $student->documents
                ->sortByDesc('uploaded_at')
                ->map(fn (Document $d) => [
                    'id' => $d->id,
                    'type' => $d->type->value,
                    'type_label' => $d->type->label(),
                    'name' => $d->original_name ?: basename((string) $d->file_path),
                    'mime_type' => $d->mime_type,
                    'size' => $d->size,
                    'uploaded_at' => $d->uploaded_at?->toIso8601String(),
                    'uploaded_by' => $d->uploader?->name,
                    'is_consent_evidence' => $d->isActiveConsentEvidence(),
                ])->values(),
            'consent' => $student->consent ? [
                'photo_allowed' => $student->consent->photo_allowed,
                'story_allowed' => $student->consent->story_allowed,
                'signed_on' => $student->consent->signed_on?->toDateString(),
                'document_id' => $student->consent->document_id,
            ] : null,
            'results' => $this->trends->annotate($student->results)->map(fn (array $row) => $this->resultRow($student, $row)),
            'fees' => $fees->map(fn (Fee $fee) => $this->feeRow($student, $fee)),
            'feeTotals' => [
                'share' => round($fees->filter->isApproved()->sum(fn (Fee $f) => (float) $f->youthline_share), 2),
                'paid' => round($fees->sum(fn (Fee $f) => $f->paidTotal()), 2),
                'balance' => round($fees->filter->isApproved()->sum(fn (Fee $f) => $f->balance()), 2),
            ],
            'finance' => [
                'currency' => $settings->currency,
                'paymentLimit' => $settings->require_payment_approval ? (float) $settings->payment_approval_limit : null,
                'scholarshipNeedsApproval' => $settings->require_scholarship_approval && ! $student->fees()->approved()->exists(),
                'gradeDropThreshold' => (float) $settings->grade_drop_threshold,
            ],
            'support' => $this->support->forStudent($student, $request->user()),
            'sponsors' => $student->sponsorships()->with('sponsor:id,name')->latest('start_date')->get()
                ->filter->isActive()
                ->map(fn ($s) => ['id' => $s->sponsor_id, 'name' => $s->sponsor?->name, 'since' => $s->start_date?->toDateString()])
                ->values(),
            'currentYear' => $currentYear?->name,
            'options' => [
                ...$this->support->options(),
                'terms' => $this->termOptions(),
                'resultTypes' => ResultType::options(),
                'paymentMethods' => Payment::METHODS,
                'transitions' => collect($student->status->allowedTransitions())->map(fn (StudentStatus $s) => [
                    'value' => $s->value, 'label' => $s->label(), 'description' => $s->description(), 'requires_reason' => $s->requiresReason(),
                ])->values(),
                'exitReasons' => ExitReason::query()->active()->get(['name', 'description']),
                'institutions' => $this->institutionOptions(),
                'levels' => EducationLevel::options(),
                'documentTypes' => DocumentType::options(),
            ],
        ]);
    }

    public function edit(Student $student): Response
    {
        return Inertia::render('Students/Form', [
            ...$this->formOptions(),
            'student' => [
                ...$student->only([
                    'id', 'first_name', 'last_name', 'gender', 'phone', 'home_location',
                    'guardian_name', 'guardian_phone', 'guardian_relationship', 'household_id',
                ]),
                'full_name' => $student->full_name,
                'date_of_birth' => $student->date_of_birth?->toDateString(),
                'program_ids' => $student->activePrograms()->pluck('programs.id'),
            ],
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $this->students->update($student, $request->validated());

        return redirect()->route('students.show', $student)->with('success', 'Student details updated.');
    }

    public function destroy(DeleteStudentRequest $request, Student $student): RedirectResponse
    {
        $name = $student->full_name;
        $this->students->delete($student);

        return redirect()->route('students.index')->with('success', "{$name} and all of their records were permanently deleted.");
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'currentYear' => $this->calendar->current()?->name,
            'households' => Household::query()->withCount('students')->orderBy('head_name')->get()
                ->map(fn (Household $h) => [
                    'value' => $h->id,
                    'label' => collect([$h->head_name ?: 'Unnamed household', $h->village_area, $h->district])->filter()->join(', ')
                        ." ({$h->students_count} ".str('student')->plural($h->students_count).')',
                ]),
            'institutions' => $this->institutionOptions(),
            'levels' => EducationLevel::options(),
            'programs' => Program::query()->orderBy('display_order')->get(['id', 'name']),
        ];
    }

    /**
     * @param  array{result: AcademicResult, change: ?float, is_drop: bool}  $row
     * @return array<string, mixed>
     */
    protected function resultRow(Student $student, array $row): array
    {
        $result = $row['result'];

        return [
            'id' => $result->id,
            'term_id' => $result->term_id,
            'term' => trim(($result->term?->academicYear?->name ?? '').' '.($result->term?->name ?? '')),
            'exam_type' => $result->exam_type->value,
            'exam_type_label' => $result->exam_type->label(),
            'level' => EducationLevel::tryFrom((string) $result->enrollment?->level)?->label(),
            'average' => $result->average !== null ? (float) $result->average : null,
            'msce_points' => $result->msce_points,
            'position' => $result->position,
            'remarks' => $result->remarks,
            'change' => $row['change'],
            'is_drop' => $row['is_drop'],
            'grades' => $result->subjectGrades->map(fn ($g) => ['subject' => $g->subject, 'grade' => $g->grade])->values(),
            'report_card_url' => $result->report_card_document_id
                ? route('students.documents.show', [$student->id, $result->report_card_document_id])
                : null,
            'recorded_by' => $result->recorder?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function feeRow(Student $student, Fee $fee): array
    {
        return [
            'id' => $fee->id,
            'term_id' => $fee->term_id,
            'term' => trim(($fee->term?->academicYear?->name ?? '').' '.($fee->term?->name ?? '')),
            'institution_id' => $fee->institution_id,
            'school' => $fee->institution?->name,
            'amount_owed' => (float) $fee->amount_owed,
            'youthline_share' => (float) $fee->youthline_share,
            'paid' => $fee->paidTotal(),
            'pending' => $fee->pendingTotal(),
            'balance' => $fee->balance(),
            'remaining' => $fee->remainingToRecord(),
            'state' => $fee->paymentState(),
            'approval_status' => $fee->approval_status->value,
            'rejection_reason' => $fee->rejection_reason,
            'notes' => $fee->notes,
            'created_by' => $fee->creator?->name,
            'payments' => $fee->payments->map(fn (Payment $p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'paid_on' => $p->paid_on?->toDateString(),
                'method' => $p->method,
                'reference' => $p->reference,
                'status' => $p->status->value,
                'status_label' => $p->status->label(),
                'recorded_by' => $p->recorder?->name,
                'approved_by' => $p->approver?->name,
                'rejection_reason' => $p->rejection_reason,
                'error_reason' => $p->error_reason,
                'error_marked_by' => $p->errorMarker?->name,
                'receipt_url' => $p->receipt_document_id
                    ? route('students.documents.show', [$student->id, $p->receipt_document_id])
                    : null,
            ])->values(),
        ];
    }

    /**
     * All terms, newest first, labelled "2025 Term 1".
     *
     * @return array<int, array{value: int, label: string, is_current: bool}>
     */
    protected function termOptions(): array
    {
        return Term::query()
            ->with('academicYear')
            ->get()
            ->sortByDesc(fn (Term $t) => $t->start_date?->timestamp ?? 0)
            ->map(fn (Term $t) => [
                'value' => $t->id,
                'label' => trim(($t->academicYear?->name ?? '').' '.$t->name),
                'is_current' => (bool) $t->is_current,
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array{value: int, label: string}>
     */
    protected function institutionOptions()
    {
        return Institution::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn (Institution $i) => ['value' => $i->id, 'label' => $i->name]);
    }
}
