<?php

namespace App\Providers;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\OrganizerProfile;
use App\Models\Withdrawal;
use App\Policies\CampaignPolicy;
use App\Policies\DonationPolicy;
use App\Policies\OrganizerProfilePolicy;
use App\Policies\WithdrawalPolicy;
use HasinHayder\TyroDashboard\Support\DashboardRoute;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(OrganizerProfile::class, OrganizerProfilePolicy::class);
        Gate::policy(Campaign::class, CampaignPolicy::class);
        Gate::policy(Donation::class, DonationPolicy::class);
        Gate::policy(Withdrawal::class, WithdrawalPolicy::class);

        // Tyro's view composer only shares `$dashboardRoute` with its own
        // namespaced (`tyro-dashboard::*`) and `dashboard.*` views. Our
        // dashboard child views (`organizer.*`, `admin.*`) extend the Tyro
        // layouts but don't match that wildcard, so we share it here too.
        View::composer(['organizer.*', 'admin.*', 'notifications.*'], function ($view) {
            $view->with('dashboardRoute', DashboardRoute::class);
        });

        // The public layout's navbar dropdown and footer need the category
        // list with live counts. Shared here so Blade never runs queries.
        View::composer('layouts.public', function ($view) {
            $view->with('navCategories', CampaignCategory::active()
                ->withCount(['campaigns' => fn ($q) => $q->publiclyVisible()])
                ->orderBy('sort_order')
                ->get());
        });

        // Send users to their role-appropriate dashboard base after login.
        // redirect()->intended() falls back to this value when there is no
        // prior intended URL, and our /role-home route redirects by role.
        config([
            'tyro-login.redirects.after_login' => '/role-home',
        ]);
    }
}
