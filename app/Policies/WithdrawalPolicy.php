<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Withdrawal;

class WithdrawalPolicy
{
    /**
     * An organizer can view their own withdrawals; admins can view any.
     */
    public function view(User $user, Withdrawal $withdrawal): bool
    {
        return $user->id === $withdrawal->organizerProfile->user_id
            || $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Only approved organizers may request a withdrawal.
     */
    public function request(User $user): bool
    {
        return $user->isApprovedOrganizer();
    }

    /**
     * Only admins may process (complete/fail) withdrawals.
     */
    public function process(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * The owner may cancel a withdrawal while it is still pending.
     */
    public function cancel(User $user, Withdrawal $withdrawal): bool
    {
        return $user->id === $withdrawal->organizerProfile->user_id
            && $withdrawal->status === 'pending';
    }
}
