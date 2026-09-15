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
        return view('admin.reports.index', ['metrics' => $reports->dashboard(), 'reports' => ReportService::REPORTS]);
    }

    public function export(Request $request, ReportService $reports): StreamedResponse
    {
        $validated = $request->validate(['report' => ['required', 'in:'.implode(',', ReportService::REPORTS)]]);
        $report = $validated['report'];

        return response()->streamDownload(function () use ($reports, $report): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $reports->headings($report));
            foreach ($reports->rows($report) as $row) {
                fputcsv($stream, array_values((array) $row));
            }
            fclose($stream);
        }, "glowy-{$report}-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
