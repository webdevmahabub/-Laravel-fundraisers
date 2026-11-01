<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;

class ReportController extends Controller
{
    /**
     * Submit a report against a campaign.
     *
     * The reported model is resolved server-side from the slug — the type
     * and ID are never trusted from the request, preventing IDOR.
     */
    public function store(StoreReportRequest $request, string $slug): RedirectResponse
    {
        $campaign = Campaign::publiclyVisible()
            ->where('slug', $slug)
            ->firstOrFail();

        // Prevent the campaign's own organizer from reporting their campaign
        if ($campaign->organizerProfile?->user_id === $request->user()->id) {
            return redirect()
                ->route('campaigns.show', $slug)
                ->with('error', 'You cannot report your own campaign.');
        }

        // One report per user per campaign — silently deduplicate
        $existing = $campaign->reports()
            ->where('reporter_user_id', $request->user()->id)
            ->exists();

        if (! $existing) {
            $campaign->reports()->create([
                'reporter_user_id' => $request->user()->id,
                'reason' => $request->validated('reason'),
                'description' => $request->validated('description'),
                'status' => 'open',
            ]);
        }

        return redirect()
            ->route('campaigns.show', $slug)
            ->with('success', 'Thank you — your report has been submitted and will be reviewed by our team.');
    }
}
