<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\RecordConsentRequest;
use App\Models\Student;
use App\Services\Students\StudentDocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:manage student documents')]
class ConsentController extends Controller
{
    public function update(RecordConsentRequest $request, Student $student, StudentDocumentService $documents): RedirectResponse
    {
        $documents->recordConsent($student, $request->validated());

        return back()->with('success', 'Consent saved. The public website follows it immediately.');
    }
}
