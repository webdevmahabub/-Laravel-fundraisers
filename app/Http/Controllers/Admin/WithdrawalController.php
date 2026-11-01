<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FailWithdrawalRequest;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use HasinHayder\Tyro\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService
    ) {}

    /**
     * List all withdrawal requests, filterable by status.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $allowed = ['pending', 'processing', 'completed', 'failed', 'rejected', 'all'];
        if (! in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $query = Withdrawal::with(['organizerProfile.user'])->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $withdrawals = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => Withdrawal::where('status', 'pending')->count(),
            'processing' => Withdrawal::where('status', 'processing')->count(),
            'completed' => Withdrawal::where('status', 'completed')->count(),
            'failed' => Withdrawal::where('status', 'failed')->count(),
        ];

        return view('admin.withdrawals.index', compact('withdrawals', 'status', 'counts'));
    }

    /**
     * Show a single withdrawal request for processing.
     */
    public function show(Withdrawal $withdrawal): View
    {
        $withdrawal->load(['organizerProfile.user', 'organizerProfile.campaigns']);

        $auditLogs = collect();
        if (config('tyro.audit.enabled', true) && class_exists(AuditLog::class)) {
            $auditLogs = AuditLog::with('user')
                ->where('auditable_type', Withdrawal::class)
                ->where('auditable_id', $withdrawal->id)
                ->orderByDesc('created_at')
                ->get();
        }

        return view('admin.withdrawals.show', compact('withdrawal', 'auditLogs'));
    }

    /**
     * Complete a withdrawal — disburses funds and writes the ledger entry.
     */
    public function complete(Withdrawal $withdrawal): RedirectResponse
    {
        $this->authorize('process', $withdrawal);

        $this->withdrawalService->complete($withdrawal, request()->user());

        return redirect()
            ->route('admin.withdrawals.show', $withdrawal)
            ->with('success', 'Withdrawal completed and ledger entry recorded.');
    }

    /**
     * Mark a withdrawal as failed.
     */
    public function fail(FailWithdrawalRequest $request, Withdrawal $withdrawal): RedirectResponse
    {
        $this->authorize('process', $withdrawal);

        $this->withdrawalService->fail(
            $withdrawal,
            $request->user(),
            $request->validated('failed_reason')
        );

        return redirect()
            ->route('admin.withdrawals.show', $withdrawal)
            ->with('success', 'Withdrawal marked as failed.');
    }
}
