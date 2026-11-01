<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Services\FeeCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignController extends Controller
{
    /** Sort options exposed to the listing. Anything else falls back to "newest". */
    private const SORTS = ['newest', 'most_funded', 'ending_soon', 'most_donated', 'recently_updated'];

    /** Status filters that map to real campaign.status values. */
    private const STATUSES = ['active', 'completed'];

    /** Progress buckets, computed from raised/goal inside the database. */
    private const PROGRESSES = ['just_started', '25', '50', '75', 'fully_funded'];

    public function __construct(
        private readonly FeeCalculationService $feeCalculationService
    ) {}

    /**
     * Browse all publicly visible campaigns.
     *
     * Search, filters and sorting are validated against whitelists here; the
     * view never writes queries. Pagination keeps every active query string
     * parameter via withQueryString().
     */
    public function index(Request $request): View
    {
        // --- Whitelisted input -------------------------------------------
        $search = trim((string) $request->query('search', ''));
        $search = $search !== '' ? mb_substr($search, 0, 100) : null;

        $sort = $request->query('sort', 'newest');
        $sort = in_array($sort, self::SORTS, true) ? $sort : 'newest';

        $status = $request->query('status');
        $status = in_array($status, self::STATUSES, true) ? $status : null;

        $progress = $request->query('progress');
        $progress = in_array($progress, self::PROGRESSES, true) ? $progress : null;

        $verifiedOnly = $request->query('verified') === '1';

        $categories = CampaignCategory::active()
            ->withCount(['campaigns' => fn ($q) => $q->publiclyVisible()])
            ->orderBy('sort_order')
            ->get();

        $categorySlug = $request->query('category');
        $selectedCategory = $categorySlug
            ? $categories->firstWhere('slug', $categorySlug)
            : null;
        if ($categorySlug && ! $selectedCategory) {
            $categorySlug = null; // Unknown slug: treat as "all categories".
        }

        // --- Query -------------------------------------------------------
        $query = Campaign::publiclyVisible()
            ->with(['organizerProfile.user', 'category'])
            ->withCount([
                'donations as donations_count' => fn ($q) => $q->completed(),
            ])
            ->addSelect([
                'donors_count' => Donation::selectRaw('COUNT(DISTINCT donor_email)')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', 'completed'),
            ]);

        // Search across title, description, organizer name and category name.
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('short_description', 'like', '%'.$search.'%')
                    ->orWhereHas('organizerProfile.user', fn ($u) => $u->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', '%'.$search.'%'));
            });
        }

        if ($categorySlug) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($verifiedOnly) {
            $query->where('is_verified', true);
        }

        if ($progress) {
            $ratio = 'COALESCE(raised_amount / NULLIF(goal_amount, 0), 0)';
            match ($progress) {
                'just_started' => $query->whereRaw($ratio.' < ?', [0.25]),
                '25' => $query->whereRaw($ratio.' >= ?', [0.25])->whereRaw($ratio.' < ?', [0.50]),
                '50' => $query->whereRaw($ratio.' >= ?', [0.50])->whereRaw($ratio.' < ?', [0.75]),
                '75' => $query->whereRaw($ratio.' >= ?', [0.75])->whereRaw($ratio.' < ?', [1]),
                'fully_funded' => $query->whereRaw($ratio.' >= ?', [1]),
                default => null, // Unreachable: validated above.
            };
        }

        // --- Sort --------------------------------------------------------
        match ($sort) {
            'most_funded' => $query->orderByDesc('raised_amount'),
            'most_donated' => $query->orderByDesc('donations_count'),
            'ending_soon' => $query
                ->orderByRaw('(end_date IS NULL OR end_date < NOW()) ASC')
                ->orderBy('end_date'),
            'recently_updated' => $query->orderByDesc('updated_at'),
            default => $query->orderByDesc('created_at'), // newest
        };

        $campaigns = $query->paginate(12)->withQueryString();

        $totalPublicCount = Campaign::publiclyVisible()->count();

        // What the view needs to render controls, chips and links.
        $filters = [
            'search' => $search,
            'sort' => $sort,
            'category' => $categorySlug,
            'status' => $status,
            'verified' => $verifiedOnly,
            'progress' => $progress,
        ];

        $hasActiveFilters = (bool) ($search || $categorySlug || $status || $verifiedOnly || $progress || $sort !== 'newest');

        return view('campaigns.index', compact(
            'campaigns',
            'categories',
            'selectedCategory',
            'totalPublicCount',
            'filters',
            'hasActiveFilters',
        ));
    }

    /**
     * Show a single public campaign page via its shareable slug.
     * URL: /fundraisers/{slug}
     */
    public function show(string $slug): View
    {
        $campaign = Campaign::publiclyVisible()
            ->with([
                // Organizer card needs the user record plus a count of this
                // organizer's publicly visible campaigns.
                'organizerProfile' => fn ($q) => $q
                    ->with('user')
                    ->withCount([
                        'campaigns as organizer_campaign_count' => fn ($c) => $c->publiclyVisible(),
                    ]),
                'category',
                'images',
                'updates' => fn ($q) => $q->latest()->limit(10),
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        // Distinct donors, not donation rows — a donor who gives twice counts once.
        $donorsCount = (int) $campaign->donations()->completed()
            ->distinct('donor_email')
            ->count('donor_email');

        // Recent verified support, paginated — anonymous gifts stay listed and
        // are shown as "Anonymous" via resolvedDonorName(). Pending and
        // cancelled (unverified) donations never appear here. The donor_page
        // page name keeps this pager independent from anything else.
        $donors = $campaign->donations()
            ->completed()
            ->latest('donated_at')
            ->latest('id')
            ->paginate(6, pageName: 'donor_page')
            ->withQueryString();

        $relatedCampaigns = Campaign::publiclyVisible()
            ->with(['category', 'organizerProfile.user', 'images'])
            ->withCount([
                'donations as donations_count' => fn ($q) => $q->completed(),
            ])
            ->addSelect([
                'donors_count' => Donation::selectRaw('COUNT(DISTINCT donor_email)')
                    ->whereColumn('donations.campaign_id', 'campaigns.id')
                    ->where('donations.status', 'completed'),
            ])
            ->where('id', '!=', $campaign->id)
            ->where(fn ($q) => $q
                ->where('category_id', $campaign->category_id)
                ->orWhere('organizer_profile_id', $campaign->organizer_profile_id)
            )
            ->orderByRaw('category_id = ? DESC', [$campaign->category_id])
            ->orderByDesc('raised_amount')
            ->limit(4)
            ->get();

        // What the donation rail is allowed to do. The same rules are enforced
        // server-side in DonationService — this only drives the UI state.
        $donateState = 'open';
        if ($campaign->status === 'completed') {
            $donateState = 'closed';
        } elseif ($campaign->status === 'paused') {
            $donateState = 'paused';
        } elseif ($campaign->end_date !== null && $campaign->end_date->isPast()) {
            $donateState = 'expired';
        } elseif ($campaign->goal_amount > 0 && (float) $campaign->raised_amount >= (float) $campaign->goal_amount) {
            $donateState = 'funded';
        }

        // Currency-appropriate quick amounts for the donation form.
        $currency = strtoupper((string) $campaign->currency);
        $quickAmounts = match ($currency) {
            'BDT' => [100, 200, 500, 1000, 2000],
            'USD' => [10, 25, 50, 100, 200],
            default => [10, 25, 50, 100, 250],
        };

        $minimumDonation = $this->feeCalculationService->minimumDonation();
        $feeRates = $this->feeCalculationService->calculate(0);

        return view('campaigns.show', compact(
            'campaign',
            'donorsCount',
            'donors',
            'relatedCampaigns',
            'donateState',
            'quickAmounts',
            'minimumDonation',
            'feeRates',
        ));
    }
}
