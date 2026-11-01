<?php

use App\Http\Controllers\Admin\CampaignModerationController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DonationReviewController;
use App\Http\Controllers\Admin\FeeConfigurationController;
use App\Http\Controllers\Admin\OrganizerVerificationController;
use App\Http\Controllers\Admin\PaymentMethodController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\ReportingController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Organizer\CampaignController as OrganizerCampaignController;
use App\Http\Controllers\Organizer\CampaignUpdateController;
use App\Http\Controllers\Organizer\WithdrawalController as OrganizerWithdrawalController;
use App\Http\Controllers\OrganizerApplicationController;
use App\Http\Controllers\ReportController;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\FeeConfiguration;
use App\Models\PaymentMethod;
use HasinHayder\TyroDashboard\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    $featuredCampaigns = Campaign::publiclyVisible()
        ->with(['category', 'organizerProfile.user', 'images'])
        ->withCount('donations')
        ->orderByDesc('raised_amount')
        ->limit(6)
        ->get();

    $categories = CampaignCategory::active()
        ->withCount(['campaigns' => fn ($q) => $q->publiclyVisible()])
        ->orderBy('sort_order')
        ->get();

    $totalCategories = $categories->count();
    $totalCampaigns = Campaign::active()->count();
    $verifiedCampaigns = Campaign::publiclyVisible()->where('is_verified', true)->count();

    // Raised totals stay per currency: mixing currencies into one figure
    // would fabricate a number, so the homepage renders each real total.
    $totalRaisedByCurrency = Donation::completed()
        ->groupBy('currency')
        ->selectRaw('currency, SUM(gross_amount) as total')
        ->pluck('total', 'currency')
        ->map(fn ($total) => (float) $total);

    $totalRaised = $totalRaisedByCurrency->sum();
    $totalDonors = (int) Donation::completed()->distinct('donor_email')->count('donor_email');

    $recentDonations = Donation::completed()
        ->with(['campaign' => fn ($q) => $q->select('id', 'title', 'slug', 'status')])
        ->latest('donated_at')
        ->limit(8)
        ->get();

    // Success story: real, verified campaign data only. Nothing invented.
    $successStory = Campaign::publiclyVisible()
        ->with(['category', 'organizerProfile.user', 'images'])
        ->withCount('donations')
        ->where('is_verified', true)
        ->orderByDesc('raised_amount')
        ->first();

    // Fee facts for the honest FAQ answers — straight from the admin config.
    $feePlatformPct = FeeConfiguration::getValue('platform_fee_pct', 5);
    $feeProcessingPct = FeeConfiguration::getValue('payment_processing_fee_pct', 2.9);
    $feeProcessingFlat = FeeConfiguration::getValue('payment_processing_fee_flat', 0.3);
    $feeWithdrawalPct = FeeConfiguration::getValue('withdrawal_fee_pct', 1);
    $feeMinWithdrawal = FeeConfiguration::getValue('minimum_withdrawal_amount', 10);

    return view('welcome', compact(
        'featuredCampaigns',
        'categories',
        'totalCategories',
        'totalCampaigns',
        'verifiedCampaigns',
        'totalRaisedByCurrency',
        'totalRaised',
        'totalDonors',
        'recentDonations',
        'successStory',
        'feePlatformPct',
        'feeProcessingPct',
        'feeProcessingFlat',
        'feeWithdrawalPct',
        'feeMinWithdrawal',
    ));
});

// Language switcher (Bangla / English) — stores the choice in the session.
Route::post('/language', function (Request $request) {
    $locale = in_array($request->input('locale'), ['en', 'bn'], true)
        ? $request->input('locale')
        : config('app.locale');

    session(['locale' => $locale]);

    return back();
})->name('language.switch');

// Public campaign browse & detail pages
Route::get('/fundraisers', [CampaignController::class, 'index'])->name('campaigns.index');
Route::get('/fundraisers/{slug}', [CampaignController::class, 'show'])->name('campaigns.show');

// Public payment methods API (for donation form)
Route::get('/api/payment-methods/{currencyType}', function (string $currencyType) {
    // Gateway-charged methods (Stripe, PayPal, bKash Merchant) require a real
    // server integration to charge and verify payments. None is wired up in
    // this codebase, so offering them would simulate a payment path that does
    // not exist. They are excluded here and only manual-transfer methods are
    // shown. Wire the gateway AND its webhook before removing a slug from
    // this list (see also StoreDonationRequest::GATEWAY_SLUGS).
    $methods = PaymentMethod::active()
        ->forCurrency(strtoupper($currencyType))
        ->whereNotIn('slug', ['stripe', 'paypal', 'bkash-merchant'])
        ->ordered()
        ->get([
            'id', 'name', 'slug', 'icon', 'description',
            'payment_instructions', 'account_holder_name', 'account_number',
            'bank_name', 'branch_name', 'routing_number', 'phone_number',
            'qr_code_image_path',
        ]);

    return response()->json($methods);
});

// Public donation (auth optional — guests may donate)
Route::post('/fundraisers/{slug}/donate', [DonationController::class, 'store'])->name('campaigns.donate');

// Report a campaign (auth required)
Route::middleware('auth')
    ->post('/fundraisers/{slug}/report', [ReportController::class, 'store'])
    ->name('campaigns.report');

/*
|--------------------------------------------------------------------------
| Organizer Application Routes (auth required)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('organizer')->name('organizer.')->group(function () {
    // Application status / landing
    Route::get('/application', [OrganizerApplicationController::class, 'show'])
        ->name('application.show');

    // Submit new application
    Route::get('/application/apply', [OrganizerApplicationController::class, 'create'])
        ->name('application.create');
    Route::post('/application/apply', [OrganizerApplicationController::class, 'store'])
        ->name('application.store');

    // Edit existing application
    Route::get('/application/edit', [OrganizerApplicationController::class, 'edit'])
        ->name('application.edit');
    Route::patch('/application/edit', [OrganizerApplicationController::class, 'update'])
        ->name('application.update');

    // Document upload / delete
    Route::post('/application/documents', [OrganizerApplicationController::class, 'uploadDocument'])
        ->name('application.documents.upload');
    Route::delete('/application/documents/{document}', [OrganizerApplicationController::class, 'deleteDocument'])
        ->name('application.documents.delete');
});

/*
|--------------------------------------------------------------------------
| Organizer Campaign Routes (approved organizer only)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'organizer'])->prefix('organizer')->name('organizer.')->group(function () {
    Route::get('/withdrawals', [OrganizerWithdrawalController::class, 'index'])
        ->name('withdrawals.index');

    Route::post('/withdrawals', [OrganizerWithdrawalController::class, 'store'])
        ->name('withdrawals.store');

    Route::delete('/withdrawals/{withdrawal}', [OrganizerWithdrawalController::class, 'cancel'])
        ->name('withdrawals.cancel');

    Route::get('/campaigns', [OrganizerCampaignController::class, 'index'])
        ->name('campaigns.index');
    Route::get('/campaigns/create', [OrganizerCampaignController::class, 'create'])
        ->name('campaigns.create');
    Route::post('/campaigns', [OrganizerCampaignController::class, 'store'])
        ->name('campaigns.store');
    Route::get('/campaigns/{campaign}', [OrganizerCampaignController::class, 'show'])
        ->name('campaigns.show');
    Route::get('/campaigns/{campaign}/edit', [OrganizerCampaignController::class, 'edit'])
        ->name('campaigns.edit');
    Route::patch('/campaigns/{campaign}', [OrganizerCampaignController::class, 'update'])
        ->name('campaigns.update');
    Route::delete('/campaigns/{campaign}', [OrganizerCampaignController::class, 'destroy'])
        ->name('campaigns.destroy');
    Route::post('/campaigns/{campaign}/submit', [OrganizerCampaignController::class, 'submit'])
        ->name('campaigns.submit');

    // Campaign updates
    Route::post('/campaigns/{campaign}/updates', [CampaignUpdateController::class, 'store'])
        ->name('campaigns.updates.store');
    Route::delete('/campaigns/{campaign}/updates/{update}', [CampaignUpdateController::class, 'destroy'])
        ->name('campaigns.updates.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin — Organizer Verification Routes
|--------------------------------------------------------------------------
| Protected by both auth and the Tyro Dashboard admin middleware.
| Route names use the "admin." prefix to avoid collision with tyro-dashboard.*
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tyro-dashboard.admin'])
    ->prefix('admin/organizer-verification')
    ->name('admin.organizer-verification.')
    ->group(function () {
        Route::get('/', [OrganizerVerificationController::class, 'index'])
            ->name('index');

        Route::get('/{organizerProfile}', [OrganizerVerificationController::class, 'show'])
            ->name('show');

        Route::post('/{organizerProfile}/review', [OrganizerVerificationController::class, 'review'])
            ->name('review');

        Route::get('/{organizerProfile}/documents/{document}/download', [OrganizerVerificationController::class, 'downloadDocument'])
            ->name('documents.download');
    });

/*
|--------------------------------------------------------------------------
| Admin — Campaign Moderation Routes
|--------------------------------------------------------------------------
| Protected by both auth and the Tyro Dashboard admin middleware.
| Route names use the "admin." prefix to avoid collision with tyro-dashboard.*
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tyro-dashboard.admin'])
    ->prefix('admin/campaigns')
    ->name('admin.campaigns.')
    ->group(function () {
        Route::get('/', [CampaignModerationController::class, 'index'])
            ->name('index');

        Route::get('/{campaign}', [CampaignModerationController::class, 'show'])
            ->name('show');

        Route::post('/{campaign}/moderate', [CampaignModerationController::class, 'moderate'])
            ->name('moderate');
    });

/*
|--------------------------------------------------------------------------
| Admin — Withdrawal Management Routes
|--------------------------------------------------------------------------
| Protected by both auth and the Tyro Dashboard admin middleware.
| Route names use the "admin." prefix to avoid collision with tyro-dashboard.*
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tyro-dashboard.admin'])
    ->prefix('admin/withdrawals')
    ->name('admin.withdrawals.')
    ->group(function () {
        Route::get('/', [AdminWithdrawalController::class, 'index'])
            ->name('index');

        Route::get('/{withdrawal}', [AdminWithdrawalController::class, 'show'])
            ->name('show');

        Route::post('/{withdrawal}/complete', [AdminWithdrawalController::class, 'complete'])
            ->name('complete');

        Route::post('/{withdrawal}/fail', [AdminWithdrawalController::class, 'fail'])
            ->name('fail');
    });

/*
|--------------------------------------------------------------------------
| Admin — Donation Verification Routes
|--------------------------------------------------------------------------
| Manual transfers (bKash, Nagad, bank transfer) are recorded as pending and
| must be verified by an admin against the actual transfer before they count
| toward a campaign. Protected by both auth and the Tyro admin middleware.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tyro-dashboard.admin'])
    ->prefix('admin/donations')
    ->name('admin.donations.')
    ->group(function () {
        Route::get('/', [DonationReviewController::class, 'index'])
            ->name('index');

        Route::post('/{donation}/verify', [DonationReviewController::class, 'verify'])
            ->name('verify');

        Route::post('/{donation}/reject', [DonationReviewController::class, 'reject'])
            ->name('reject');
    });

/*
|--------------------------------------------------------------------------
| Admin — Fee Configuration Routes
|--------------------------------------------------------------------------
| Protected by both auth and the Tyro Dashboard admin middleware.
| Route names use the "admin." prefix to avoid collision with tyro-dashboard.*
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tyro-dashboard.admin'])
    ->prefix('admin/fee-config')
    ->name('admin.fee-config.')
    ->group(function () {
        Route::get('/', [FeeConfigurationController::class, 'index'])
            ->name('index');

        Route::put('/', [FeeConfigurationController::class, 'update'])
            ->name('update');
    });

/*
|--------------------------------------------------------------------------
| Admin — Payment Method Management Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tyro-dashboard.admin'])
    ->prefix('admin/payment-methods')
    ->name('admin.payment-methods.')
    ->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index'])
            ->name('index');

        Route::get('/create', [PaymentMethodController::class, 'create'])
            ->name('create');

        Route::post('/', [PaymentMethodController::class, 'store'])
            ->name('store');

        Route::get('/{paymentMethod}/edit', [PaymentMethodController::class, 'edit'])
            ->name('edit');

        Route::put('/{paymentMethod}', [PaymentMethodController::class, 'update'])
            ->name('update');

        Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])
            ->name('destroy');

        Route::post('/{paymentMethod}/toggle', [PaymentMethodController::class, 'toggle'])
            ->name('toggle');
    });

/*
|--------------------------------------------------------------------------
| Admin — Reporting Routes (financial summary)
|--------------------------------------------------------------------------
| Protected by both auth and the Tyro Dashboard admin middleware.
| Route names use the "admin." prefix to avoid collision with tyro-dashboard.*
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tyro-dashboard.admin'])
    ->prefix('admin/reports')
    ->name('admin.reports.')
    ->group(function () {
        Route::get('/', [ReportingController::class, 'index'])
            ->name('index');
    });

/*
|--------------------------------------------------------------------------
| Admin — User Report Moderation Routes (reported content)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tyro-dashboard.admin'])
    ->prefix('admin/user-reports')
    ->name('admin.user-reports.')
    ->group(function () {
        Route::get('/', [AdminReportController::class, 'index'])
            ->name('index');

        Route::get('/{report}', [AdminReportController::class, 'show'])
            ->name('show');

        Route::post('/{report}/resolve', [AdminReportController::class, 'resolve'])
            ->name('resolve');
    });

/*
|--------------------------------------------------------------------------
| Role-based Dashboard Bases
|--------------------------------------------------------------------------
| Each role lands on its own dashboard URL. All delegate to Tyro's
| DashboardController, which renders the admin or user dashboard based on
| the authenticated user's role. The custom /organizer/* and /dashboard/*
| feature routes are unchanged.
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('tyro-dashboard.admin')
        ->name('admin.dashboard');

    Route::get('/organizer/dashboard', [DashboardController::class, 'index'])
        ->name('organizer.dashboard');

    // Post-login landing — redirects to the role-appropriate base above.
    Route::get('/role-home', DashboardRedirectController::class)
        ->name('role.home');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])
        ->name('notifications.markAllRead');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.markRead');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('notifications.unreadCount');
});
