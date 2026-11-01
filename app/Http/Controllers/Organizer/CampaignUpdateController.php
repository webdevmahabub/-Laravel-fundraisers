<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignUpdateRequest;
use App\Models\Campaign;
use App\Models\CampaignUpdate;
use Illuminate\Http\RedirectResponse;

class CampaignUpdateController extends Controller
{
    /**
     * Post a new update to a campaign.
     * Only the campaign owner may post updates, and only on active/paused campaigns.
     */
    public function store(StoreCampaignUpdateRequest $request, Campaign $campaign): RedirectResponse
    {
        // IDOR guard — must own this campaign
        abort_unless(
            $campaign->organizerProfile->user_id === $request->user()->id,
            403
        );

        abort_unless(
            in_array($campaign->status, ['active', 'paused', 'completed'], true),
            403,
            'Updates can only be posted on active, paused, or completed campaigns.'
        );

        $campaign->updates()->create([
            'user_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'body' => $request->validated('body'),
        ]);

        return redirect()->route('organizer.campaigns.show', $campaign)
            ->with('success', 'Update posted successfully.');
    }

    /**
     * Delete a campaign update.
     * Only the campaign owner may delete updates.
     */
    public function destroy(Campaign $campaign, CampaignUpdate $update): RedirectResponse
    {
        abort_unless(
            $campaign->organizerProfile->user_id === auth()->id(),
            403
        );

        abort_unless($update->campaign_id === $campaign->id, 404);

        $update->delete();

        return redirect()->route('organizer.campaigns.show', $campaign)
            ->with('success', 'Update deleted.');
    }
}
