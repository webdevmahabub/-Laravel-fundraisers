<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    /**
     * Send the authenticated user to their role-appropriate dashboard base.
     *
     * Used as the post-login landing target so each role opens the correct
     * dashboard: admin -> /admin/dashboard, organizer -> /organizer/dashboard,
     * regular user -> /dashboard.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        $adminRoles = config('tyro-dashboard.admin_roles', ['admin', 'super-admin']);

        if ($user->hasAnyRole($adminRoles)) {
            return redirect()->to('/admin/dashboard');
        }

        if ($user->isApprovedOrganizer()) {
            return redirect()->to('/organizer/dashboard');
        }

        return redirect()->to('/dashboard');
    }
}
