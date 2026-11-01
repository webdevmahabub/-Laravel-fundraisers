<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Notifications\Notification;

class CampaignApproved extends Notification
{
    public function __construct(public Campaign $campaign) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'campaign_approved',
            'title' => 'Campaign approved',
            'body' => 'Your campaign "'.$this->campaign->title.'" has been approved and is now publicly visible.',
            'campaign_id' => $this->campaign->id,
            'campaign_slug' => $this->campaign->slug,
        ];
    }
}
