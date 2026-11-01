<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Notifications\Notification;

class CampaignSubmittedForReview extends Notification
{
    public function __construct(public Campaign $campaign) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'campaign_submitted_for_review',
            'title' => 'New campaign awaiting review',
            'body' => 'Campaign "'.$this->campaign->title.'" was submitted by '.$this->campaign->organizerProfile?->full_name.' and needs review.',
            'campaign_id' => $this->campaign->id,
            'campaign_slug' => $this->campaign->slug,
        ];
    }
}
