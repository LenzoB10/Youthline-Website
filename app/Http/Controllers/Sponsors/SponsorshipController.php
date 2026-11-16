<?php

namespace App\Http\Controllers\Sponsors;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sponsors\ProgressUpdateRequest;
use App\Http\Requests\Sponsors\SponsorshipRequest;
use App\Models\Sponsor;
use App\Models\Sponsorship;
use App\Services\Reports\Exporter;
use App\Services\Sponsors\SponsorReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('permission:manage sponsors')]
class SponsorshipController extends Controller
{
    public function store(SponsorshipRequest $request, Sponsor $sponsor): RedirectResponse
    {
        $sponsor->sponsorships()->create($request->validated());

        return back()->with('success', 'Student linked to this sponsor.');
    }

    public function update(SponsorshipRequest $request, Sponsor $sponsor, Sponsorship $sponsorship): RedirectResponse
    {
        $sponsorship->update($request->safe()->except('student_id'));

        return back()->with('success', 'Sponsorship updated.');
    }

    /**
     * The short note that goes into the sponsor's report.
     */
    public function progressUpdate(ProgressUpdateRequest $request, Sponsor $sponsor, Sponsorship $sponsorship): RedirectResponse
    {
        $sponsorship->update([
            'progress_update' => $request->validated('progress_update'),
            'progress_update_on' => now()->toDateString(),
            'progress_update_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Progress update saved.');
    }

    /**
     * The sponsor's progress report as a PDF, from live data.
     */
    public function report(Sponsor $sponsor, SponsorReportService $reports, Exporter $exporter): Response
    {
        return $exporter->pdf('reports.sponsor', ['report' => $reports->build($sponsor)], 'progress-report-'.str($sponsor->name)->slug().'-'.now()->format('Y-m-d'));
    }
}
