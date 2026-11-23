<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Term;
use App\Services\Fees\BalanceService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Middleware('permission:manage fees')]
class UnpaidFeesController extends Controller
{
    public function __construct(
        protected BalanceService $balances,
        protected SystemSettingsService $settings,
    ) {}

    public function index(Request $request): Response
    {
        $termId = $this->termId($request);

        return Inertia::render('Finance/UnpaidFees', [
            'currency' => $this->settings->get()->currency,
            'termId' => $termId,
            'terms' => $this->termOptions(),
            'totals' => $this->balances->totals($termId),
            'students' => $this->balances->unpaidByStudent($termId),
            'schools' => $this->balances->unpaidBySchool($termId),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $termId = $this->termId($request);
        $rows = $this->balances->unpaidByStudent($termId);
        $currency = $this->settings->get()->currency;

        return response()->streamDownload(function () use ($rows, $currency) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student', 'Status', 'School(s)', 'Term(s)', "Youthline share ({$currency})", "Paid ({$currency})", "Balance ({$currency})"]);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $this->safe($row['student']),
                    $row['student_status'],
                    $this->safe(implode('; ', $row['schools'])),
                    implode('; ', $row['terms']),
                    number_format($row['share'], 2, '.', ''),
                    number_format($row['paid'], 2, '.', ''),
                    number_format($row['balance'], 2, '.', ''),
                ]);
            }
            fclose($out);
        }, 'unpaid-fees-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    protected function termId(Request $request): ?int
    {
        $id = $request->integer('term') ?: null;

        return $id && Term::query()->whereKey($id)->exists() ? $id : null;
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    protected function termOptions(): array
    {
        return Term::query()
            ->with('academicYear')
            ->get()
            ->sortByDesc(fn (Term $t) => $t->start_date?->timestamp ?? 0)
            ->map(fn (Term $t) => ['value' => $t->id, 'label' => trim(($t->academicYear?->name ?? '').' '.$t->name)])
            ->values()
            ->all();
    }

    /**
     * Stop spreadsheet apps treating a name like "=SUM(...)" as a formula.
     */
    protected function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
