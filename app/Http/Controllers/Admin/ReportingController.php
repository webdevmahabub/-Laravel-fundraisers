<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\View\View;

class ReportingController extends Controller
{
    public function __construct(
        protected ReportingService $reporting
    ) {}

    /**
     * Show the platform-wide financial reporting dashboard.
     */
    public function index(): View
    {
        $report = $this->reporting->summary();

        return view('admin.reports.index', compact('report'));
    }
}
