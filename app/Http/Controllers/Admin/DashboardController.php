<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportingService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportingService $reporting
    ) {}

    public function index(): View
    {
        $report = $this->reporting->summary();
        $charts = $this->reporting->charts();

        return view('admin.dashboard', compact('report', 'charts'));
    }
}
