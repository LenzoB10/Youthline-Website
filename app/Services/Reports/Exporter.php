<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Turns a report (title + headings + rows) into an Excel workbook or a PDF.
 */
class Exporter
{
    /**
     * @param  array{title: string, subtitle?: ?string, headings: array<int, string>, rows: array<int, array<int, mixed>>}  $report
     */
    public function xlsx(array $report, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($report) {
            $writer = new Writer;
            $writer->openToFile('php://output');
            $bold = (new Style)->withFontBold(true);

            $writer->addRow(Row::fromValuesWithStyle([$report['title']], $bold));
            if (! empty($report['subtitle'])) {
                $writer->addRow(Row::fromValues([$report['subtitle']]));
            }
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValuesWithStyle($report['headings'], $bold));
            foreach ($report['rows'] as $row) {
                $writer->addRow(Row::fromValues(array_map(fn ($value) => $this->cell($value), $row)));
            }
            $writer->close();
        }, $filename.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * A table report as a PDF (landscape when it has many columns).
     *
     * @param  array{title: string, subtitle?: ?string, headings: array<int, string>, rows: array<int, array<int, mixed>>}  $report
     */
    public function tablePdf(array $report, string $filename): Response
    {
        return $this->pdf('reports.table', ['report' => $report], $filename, count($report['headings']) > 5 ? 'landscape' : 'portrait');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function pdf(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        return Pdf::loadView($view, [...$data, 'organisation' => config('app.name', 'Youthline')])
            ->setPaper('a4', $orientation)
            ->download($filename.'.pdf');
    }

    /**
     * Spreadsheet apps run text starting with = + - @ as a formula; neutralise it.
     */
    protected function cell(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'".$value;
        }

        return $value ?? '';
    }
}
