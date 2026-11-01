<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateCampaignRequest;
use App\Models\Campaign;
use App\Services\CampaignService;
use HasinHayder\Tyro\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignModerationController extends Controller
{
    public function __construct(
        private readonly CampaignService $campaignService
    ) {}

    /**
     * List all campaigns, filterable by status.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $allowedStatuses = [
            'draft', 'pending', 'active', 'paused',
            'completed', 'cancelled', 'rejected', 'suspended', 'all',
        ];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }

        $query = Campaign::with(['organizerProfile.user', 'category'])
            ->withCount('donations')
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $campaigns = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => Campaign::where('status', 'pending')->count(),
            'active' => Campaign::where('status', 'active')->count(),
            'paused' => Campaign::where('status', 'paused')->count(),
            'suspended' => Campaign::where('status', 'suspended')->count(),
            'rejected' => Campaign::where('status', 'rejected')->count(),
            'completed' => Campaign::where('status', 'completed')->count(),
        ];

        return view('admin.campaigns.index', compact('campaigns', 'status', 'counts'));
    }

    /**
     * Show a single campaign for moderation.
     */
    public function show(Campaign $campaign): View
    {
        $campaign->load([
            'organizerProfile.user',
            'category',
            'images',
            'updates' => fn ($q) => $q->latest()->limit(10),
        ]);

        $donorsCount = $campaign->donations()->completed()->count();

        $auditLogs = collect();
        if (config('tyro.audit.enabled', true) && class_exists(AuditLog::class)) {
            $auditLogs = AuditLog::with('user')
                ->where('auditable_type', Campaign::class)
                ->where('auditable_id', $campaign->id)
                ->orderByDesc('created_at')
                ->get();
        }

        return view('admin.campaigns.show', compact('campaign', 'donorsCount', 'auditLogs'));
    }

    /**
     * Process a moderation action (approve / reject / pause / suspend / reactivate).
     */
    public function moderate(ModerateCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        $this->authorize('moderate', $campaign);

        $action = $request->validated('action');
        $admin = $request->user();

        match ($action) {
            'approve' => $this->campaignService->approve($campaign, $admin),
            'reject' => $this->campaignService->reject(
                $campaign,
                $admin,
                $request->validated('rejection_reason')
            ),
            'pause' => $this->campaignService->pause($campaign, $admin),
            'suspend' => $this->campaignService->suspend($campaign, $admin),
            'reactivate' => $this->campaignService->reactivate($campaign, $admin),
        };

        $messages = [
            'approve' => 'Campaign approved and is now publicly visible.',
            'reject' => 'Campaign rejected.',
            'pause' => 'Campaign paused.',
            'suspend' => 'Campaign suspended.',
            'reactivate' => 'Campaign reactivated.',
        ];

        return redirect()
            ->route('admin.campaigns.show', $campaign)
            ->with('success', $messages[$action]);
    }
}
