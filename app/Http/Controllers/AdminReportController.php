<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $tab = in_array($request->query('tab'), ['open', 'reviewed', 'all'], true) ? $request->query('tab') : 'open';

        $reports = Report::with('reporter')
            ->when($tab !== 'all', fn ($q) => $q->where('status', $tab))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'open' => Report::where('status', 'open')->count(),
            'reviewed' => Report::where('status', 'reviewed')->count(),
            'all' => Report::count(),
        ];

        return view('admin.reports', compact('reports', 'tab', 'counts'));
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $report->update(['status' => $report->status === 'open' ? 'reviewed' : 'open']);

        AdminLog::record($request->user(), $report->status === 'reviewed' ? 'report.reviewed' : 'report.reopened', 'report', $report->id, 'بلاغ #'.$report->id);

        return back()->with('success', $report->status === 'reviewed' ? 'تم وضع علامة «تمت المراجعة» على البلاغ.' : 'أُعيد فتح البلاغ.');
    }
}
