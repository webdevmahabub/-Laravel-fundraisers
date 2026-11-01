<?php

namespace App\Notifications;

use App\Models\OrganizerProfile;
use Illuminate\Notifications\Notification;

class OrganizerApplicationApproved extends Notification
{
    public function __construct(public OrganizerProfile $profile) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'organizer_application_approved',
            'title' => 'Organizer application approved',
            'body' => 'Your organizer application has been approved. You can now create fundraising campaigns.',
            'profile_id' => $this->profile->id,
        ];
    }
}
