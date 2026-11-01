<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsApprovedOrganizer
{
    /**
     * Only allow users with an approved organizer profile to proceed.
     * Redirects to the application status page otherwise.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isApprovedOrganizer()) {
            return redirect()
                ->route('organizer.application.show')
                ->with('warning', 'You must be an approved organizer to access this area.');
        }

        return $next($request);
    }
}
