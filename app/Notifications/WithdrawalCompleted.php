<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Notifications\Notification;

class WithdrawalCompleted extends Notification
{
    public function __construct(public Withdrawal $withdrawal) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'withdrawal_completed',
            'title' => 'Withdrawal completed',
            'body' => 'Your withdrawal of '.$this->withdrawal->currency.' '.number_format($this->withdrawal->net_amount, 2)
                .' has been completed.',
            'withdrawal_id' => $this->withdrawal->id,
            'amount' => $this->withdrawal->net_amount,
            'currency' => $this->withdrawal->currency,
        ];
    }
}
