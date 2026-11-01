<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Services\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService
    ) {}

    /**
     * List all campaigns owned by the authenticated organizer.
     */
    public function index(Request $request): View
    {
        $organizerProfile = $request->user()->organizerProfile;

        $campaigns = $organizerProfile
            ->campaigns()
            ->with('category')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('organizer.campaigns.index', compact('campaigns'));
    }

    /**
     * Show the create campaign form.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', Campaign::class);

        $categories = CampaignCategory::active()->orderBy('sort_order')->get();

        return view('organizer.campaigns.create', compact('categories'));
    }

    /**
     * Store a new campaign draft.
     */
    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $campaign = $this->campaignService->create(
            $request->user()->organizerProfile,
            $request->validated() + ['cover_image' => $request->file('cover_image')]
        );

        return redirect()->route('organizer.campaigns.show', $campaign)
            ->with('success', 'Campaign created as a draft. Review it and submit when ready.');
    }

    /**
     * Show a single campaign detail page for the organizer.
     */
    public function show(Request $request, Campaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $campaign->load(['category', 'images', 'updates' => fn ($q) => $q->latest()->limit(5)]);

        return view('organizer.campaigns.show', compact('campaign'));
    }

    /**
     * Show the edit form for a campaign.
     */
    public function edit(Request $request, Campaign $campaign): View
    {
        $this->authorize('update', $campaign);

        $categories = CampaignCategory::active()->orderBy('sort_order')->get();

        return view('organizer.campaigns.edit', compact('campaign', 'categories'));
    }

    /**
     * Update a campaign.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('update', $campaign);

        $this->campaignService->update(
            $campaign,
            $request->validated() + ['cover_image' => $request->file('cover_image')]
        );

        return redirect()->route('organizer.campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    /**
     * Delete a draft campaign.
     */
    public function destroy(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('delete', $campaign);

        $campaign->delete();

        return redirect()->route('organizer.campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    /**
     * Submit a draft campaign for admin review.
     */
    public function submit(Request $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('submit', $campaign);

        $this->campaignService->submit($campaign, $request->user());

        return redirect()->route('organizer.campaigns.show', $campaign)
            ->with('success', 'Campaign submitted for review. You will be notified once it is approved.');
    }
}
