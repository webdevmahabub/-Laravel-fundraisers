<?php

namespace App\Notifications;

use App\Models\Campaign;
use Illuminate\Notifications\Notification;

class CampaignRejected extends Notification
{
    public function __construct(
        public Campaign $campaign,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'campaign_rejected',
            'title' => 'Campaign rejected',
            'body' => 'Your campaign "'.$this->campaign->title.'" was rejected. Reason: '.$this->reason,
            'campaign_id' => $this->campaign->id,
            'campaign_slug' => $this->campaign->slug,
            'reason' => $this->reason,
        ];
    }
}
