<?php

namespace App\Http\Controllers\Students;

use App\Enums\DocumentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentDocumentRequest;
use App\Models\Document;
use App\Models\Student;
use App\Services\Students\StudentDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documents are served only through this controller (never a public URL), and the
 * route is scoped so a document can only be reached through its own student.
 */
#[Middleware('permission:list students', only: ['show'])]
#[Middleware('permission:manage student documents', only: ['store'])]
#[Middleware('permission:delete student documents', only: ['destroy'])]
class StudentDocumentController extends Controller
{
    public function __construct(protected StudentDocumentService $documents) {}

    public function store(StoreStudentDocumentRequest $request, Student $student): RedirectResponse
    {
        $type = DocumentType::from($request->validated('type'));
        $this->documents->store($student, $request->file('file'), $type, $request->user());

        return back()->with('success', "{$type->label()} uploaded.");
    }

    public function show(Request $request, Student $student, Document $document): StreamedResponse
    {
        abort_unless($this->documents->exists($document), 404, 'The file for this document could not be found.');

        return $this->documents->download($document, inline: ! $request->boolean('download'));
    }

    public function destroy(Student $student, Document $document): RedirectResponse
    {
        if ($document->isActiveConsentEvidence()) {
            return back()->with('error', 'This signed consent form backs the current photo/story consent. Change the consent first.');
        }

        if ($document->isRecordEvidence()) {
            return back()->with('error', 'This file is the receipt or report card behind a recorded payment or result, so it is kept with that record.');
        }

        $this->documents->delete($document);

        return back()->with('success', 'Document deleted.');
    }
}
