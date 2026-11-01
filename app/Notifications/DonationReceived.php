<?php

namespace App\Notifications;

use App\Models\Donation;
use Illuminate\Notifications\Notification;

class DonationReceived extends Notification
{
    public function __construct(public Donation $donation) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $campaign = $this->donation->campaign;

        return [
            'type' => 'donation_received',
            'title' => 'New donation received',
            'body' => 'You received a donation of '.$this->donation->currency.' '.number_format($this->donation->gross_amount, 2)
                .' on "'.($campaign?->title ?? 'your campaign').'".',
            'donation_id' => $this->donation->id,
            'campaign_id' => $campaign?->id,
            'campaign_slug' => $campaign?->slug,
            'amount' => $this->donation->gross_amount,
            'currency' => $this->donation->currency,
        ];
    }
}
