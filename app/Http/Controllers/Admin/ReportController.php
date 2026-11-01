<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolveReportRequest;
use App\Models\Report;
use HasinHayder\Tyro\Support\TyroAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * List all reports, filterable by status.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'open');

        $allowed = ['open', 'under_review', 'resolved', 'dismissed', 'all'];
        if (! in_array($status, $allowed, true)) {
            $status = 'open';
        }

        $query = Report::with(['reporter', 'resolver', 'reportable'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $reports = $query->paginate(20)->withQueryString();

        $counts = [
            'open' => Report::where('status', 'open')->count(),
            'under_review' => Report::where('status', 'under_review')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'dismissed' => Report::where('status', 'dismissed')->count(),
        ];

        return view('admin.reports.moderation', compact('reports', 'status', 'counts'));
    }

    /**
     * Show a single report for review.
     */
    public function show(Report $report): View
    {
        $report->load(['reporter', 'resolver', 'reportable']);

        return view('admin.reports.show', compact('report'));
    }

    /**
     * Process a moderation action on a report.
     */
    public function resolve(ResolveReportRequest $request, Report $report): RedirectResponse
    {
        $action = $request->validated('action');
        $oldStatus = $report->status;

        $report->status = $action;
        $report->resolved_by = $action !== 'under_review' ? $request->user()->id : $report->resolved_by;
        $report->resolved_at = $action !== 'under_review' ? now() : $report->resolved_at;
        $report->save();

        TyroAudit::log('report.'.$action, $report, ['status' => $oldStatus], [
            'status' => $action,
            'admin_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.user-reports.index')
            ->with('success', 'Report marked as '.str_replace('_', ' ', $action).'.');
    }
}
