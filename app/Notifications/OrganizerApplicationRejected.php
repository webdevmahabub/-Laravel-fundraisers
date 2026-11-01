<?php

namespace App\Notifications;

use App\Models\OrganizerProfile;
use Illuminate\Notifications\Notification;

class OrganizerApplicationRejected extends Notification
{
    public function __construct(
        public OrganizerProfile $profile,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'organizer_application_rejected',
            'title' => 'Organizer application rejected',
            'body' => 'Your organizer application was rejected. Reason: '.$this->reason,
            'profile_id' => $this->profile->id,
            'reason' => $this->reason,
        ];
    }
}
