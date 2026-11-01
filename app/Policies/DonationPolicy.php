<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;

class DonationPolicy
{
    /**
     * A donor can view their own donation. Admins can view any.
     */
    public function view(User $user, Donation $donation): bool
    {
        return $user->id === $donation->user_id
            || $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Donations are immutable — nobody can update them through normal flows.
     * Only admin refund/void processing would touch a donation, handled separately.
     */
    public function update(User $user, Donation $donation): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }
}
