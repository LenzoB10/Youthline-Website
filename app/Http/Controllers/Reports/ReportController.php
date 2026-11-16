<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\Exporter;
use App\Services\Reports\ReportCatalog;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

#[Middleware('permission:export reports')]
class ReportController extends Controller
{
    public function __construct(protected ReportCatalog $catalog) {}

    public function index(Request $request): Response
    {
        $allowed = $this->catalog->allowedFor($request->user());

        return Inertia::render('Reports/Index', [
            'reports' => collect($this->catalog->definitions())
                ->only($allowed)
                ->map(fn ($def, $key) => ['key' => $key, ...$def])
                ->values(),
        ]);
    }

    public function export(Request $request, string $report, string $format, Exporter $exporter): HttpResponse
    {
        abort_unless(in_array($report, $this->catalog->allowedFor($request->user()), true), 403);
        abort_unless(in_array($format, ['xlsx', 'pdf'], true), 404);

        $data = $this->catalog->build($report);
        $filename = $report.'-'.now()->format('Y-m-d');

        return $format === 'xlsx' ? $exporter->xlsx($data, $filename) : $exporter->tablePdf($data, $filename);
    }
}
