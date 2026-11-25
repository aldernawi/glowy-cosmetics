<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportService $reports): View
    {
        $filters = request()->validate($this->filterRules());

        return view('admin.reports.index', ['metrics' => $reports->dashboard($filters), 'reports' => ReportService::REPORTS, 'filters' => $filters, 'channels' => ReportService::CHANNELS]);
    }

    public function export(Request $request, ReportService $reports): StreamedResponse
    {
        $validated = $request->validate(['report' => ['required', 'in:'.implode(',', ReportService::REPORTS)]] + $this->filterRules());
        $report = $validated['report'];

        return response()->streamDownload(function () use ($reports, $report, $validated): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $reports->headings($report), escape: '');
            foreach ($reports->rows($report, $validated) as $row) {
                fputcsv($stream, array_map($this->safeCell(...), array_values((array) $row)), escape: '');
            }
            fclose($stream);
        }, "glowy-{$report}-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string, array<int, string>> */
    private function filterRules(): array
    {
        return [
            'channel' => ['nullable', 'in:all,'.implode(',', array_keys(ReportService::CHANNELS))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'status' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'in:unpaid,paid'],
        ];
    }

    /** Customer-provided text must never run as a formula when the file is opened in a spreadsheet. */
    private function safeCell(mixed $value): mixed
    {
        return is_string($value) && ! is_numeric($value) && preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
