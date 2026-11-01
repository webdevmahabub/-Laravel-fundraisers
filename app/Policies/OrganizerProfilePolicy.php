<?php

namespace App\Policies;

use App\Models\OrganizerProfile;
use App\Models\User;

class OrganizerProfilePolicy
{
    /**
     * Any authenticated user can attempt to view the application page.
     * The controller decides what to show based on whether they have one.
     */
    public function view(User $user, OrganizerProfile $profile): bool
    {
        return $user->id === $profile->user_id
            || $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * A user can update their own application while it is pending or rejected.
     */
    public function update(User $user, OrganizerProfile $profile): bool
    {
        return $user->id === $profile->user_id
            && in_array($profile->status, ['pending', 'rejected'], true);
    }

    /**
     * Only admins can approve an application.
     */
    public function approve(User $user, OrganizerProfile $profile): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin'])
            && in_array($profile->status, ['pending', 'under_review'], true);
    }

    /**
     * Only admins can reject an application.
     */
    public function reject(User $user, OrganizerProfile $profile): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin'])
            && in_array($profile->status, ['pending', 'under_review'], true);
    }

    /**
     * Only admins can upload/manage another user's documents.
     */
    public function manageDocuments(User $user, OrganizerProfile $profile): bool
    {
        return $user->id === $profile->user_id
            || $user->hasAnyRole(['admin', 'super-admin']);
    }
}
