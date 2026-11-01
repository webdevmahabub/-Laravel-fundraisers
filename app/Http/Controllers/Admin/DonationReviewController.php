<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Services\DonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verification queue for manual-transfer donations.
 *
 * Donations made via bKash, Nagad or bank transfer are recorded as pending.
 * An admin checks the transfer (sender mobile + provider TrxID) and either
 * verifies it — which writes the ledger entry and updates raised_amount —
 * or rejects it. Financial state only ever changes in DonationService.
 */
class DonationReviewController extends Controller
{
    private const STATUSES = ['pending', 'completed', 'cancelled'];

    public function __construct(
        private readonly DonationService $donationService
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');
        if (! in_array($status, [...self::STATUSES, 'all'], true)) {
            $status = 'pending';
        }

        $query = Donation::with('campaign')
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $donations = $query->paginate(20)->withQueryString();

        $counts = [];
        foreach (self::STATUSES as $key) {
            $counts[$key] = Donation::where('status', $key)->count();
        }

        return view('admin.donations.index', compact('donations', 'status', 'counts'));
    }

    /**
     * Confirm the transfer and complete the donation.
     */
    public function verify(Request $request, Donation $donation): RedirectResponse
    {
        $this->donationService->complete($donation, $request->user());

        return redirect()
            ->route('admin.donations.index', ['status' => $request->query('status', 'pending')])
            ->with('success', 'Donation verified. The amount has been added to the campaign.');
    }

    /**
     * The transfer could not be confirmed — cancel the pending donation.
     */
    public function reject(Request $request, Donation $donation): RedirectResponse
    {
        $this->donationService->reject($donation, $request->user());

        return redirect()
            ->route('admin.donations.index', ['status' => $request->query('status', 'pending')])
            ->with('success', 'Donation rejected. It no longer appears in the pending queue.');
    }
}
