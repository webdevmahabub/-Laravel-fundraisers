<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWithdrawalRequest;
use App\Models\FeeConfiguration;
use App\Models\Withdrawal;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function __construct(
        private readonly WithdrawalService $withdrawalService
    ) {}

    /**
     * Show the organizer's withdrawals, available balance, and request form.
     */
    public function index(Request $request): View
    {
        $profile = $request->user()->organizerProfile;

        $available = $this->withdrawalService->availableBalance($profile);

        $withdrawals = $profile->withdrawals()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $feePct = FeeConfiguration::getValue('withdrawal_fee_pct', 1.0);
        $minimum = FeeConfiguration::getValue('minimum_withdrawal_amount', 10.0);

        return view('organizer.withdrawals.index', compact('available', 'withdrawals', 'feePct', 'minimum'));
    }

    /**
     * Submit a new withdrawal request.
     */
    public function store(StoreWithdrawalRequest $request): RedirectResponse
    {
        $this->authorize('request', Withdrawal::class);

        $profile = $request->user()->organizerProfile;

        $data = $request->validated();

        if ($request->filled('account_details')) {
            $data['payment_details'] = ['details' => $request->input('account_details')];
        }

        $this->withdrawalService->request($profile, $data);

        return redirect()
            ->route('organizer.withdrawals.index')
            ->with('success', 'Withdrawal request submitted. Our team will process it shortly.');
    }

    /**
     * Cancel a pending withdrawal request.
     */
    public function cancel(Request $request, Withdrawal $withdrawal): RedirectResponse
    {
        $this->authorize('cancel', $withdrawal);

        $this->withdrawalService->cancel($withdrawal);

        return redirect()
            ->route('organizer.withdrawals.index')
            ->with('success', 'Withdrawal request cancelled.');
    }
}
