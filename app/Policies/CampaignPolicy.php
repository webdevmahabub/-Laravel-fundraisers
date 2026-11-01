<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /**
     * View a campaign — public campaigns are open to everyone (handled at controller level).
     * This policy governs dashboard/owner access.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        return $user->id === $campaign->organizerProfile->user_id
            || $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Only approved organizers can create campaigns.
     */
    public function create(User $user): bool
    {
        return $user->isApprovedOrganizer();
    }

    /**
     * Only the campaign owner can edit it, and only in editable statuses.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        return $user->id === $campaign->organizerProfile->user_id
            && in_array($campaign->status, ['draft', 'rejected', 'paused'], true);
    }

    /**
     * Only the campaign owner can delete draft campaigns.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->id === $campaign->organizerProfile->user_id
            && $campaign->status === 'draft';
    }

    /**
     * Only the campaign owner can submit for review.
     */
    public function submit(User $user, Campaign $campaign): bool
    {
        return $user->id === $campaign->organizerProfile->user_id
            && $campaign->status === 'draft';
    }

    /**
     * Admin moderation: approve, reject, pause, suspend.
     */
    public function moderate(User $user, Campaign $campaign): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }
}
