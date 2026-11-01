<?php

namespace App\Notifications;

use App\Models\OrganizerProfile;
use Illuminate\Notifications\Notification;

class OrganizerApplicationSubmitted extends Notification
{
    public function __construct(public OrganizerProfile $profile) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'organizer_application_submitted',
            'title' => 'New organizer application',
            'body' => 'A new organizer application from '.$this->profile->full_name.' is awaiting review.',
            'profile_id' => $this->profile->id,
        ];
    }
}
