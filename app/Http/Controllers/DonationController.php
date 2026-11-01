<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDonationRequest;
use App\Models\Campaign;
use App\Services\DonationService;
use Illuminate\Http\RedirectResponse;

class DonationController extends Controller
{
    public function __construct(
        private readonly DonationService $donationService
    ) {}

    /**
     * Process a donation to a campaign identified by its public slug.
     *
     * The gross_amount comes from the user, but ALL financial values
     * (platform fee, processing fee, net amount) are computed entirely
     * server-side inside DonationService — nothing from the client is trusted.
     */
    public function store(StoreDonationRequest $request, string $slug): RedirectResponse
    {
        $campaign = Campaign::active()
            ->where('slug', $slug)
            ->firstOrFail();

        $donation = $this->donationService->donate(
            $campaign,
            $request->validated(),
            $request->user()   // null for guests
        );

        $message = $donation->status === 'pending'
            ? 'Thank you! Your donation has been recorded and is now awaiting verification. Our team will confirm your payment shortly — once approved, your gift will appear on this campaign page.'
            : 'Thank you for your donation! Your contribution makes a real difference.';

        return redirect()
            ->route('campaigns.show', $slug)
            ->with('success', $message);
    }
}
