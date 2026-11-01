<?php

namespace App\Notifications;

use App\Models\Withdrawal;
use Illuminate\Notifications\Notification;

class WithdrawalFailed extends Notification
{
    public function __construct(
        public Withdrawal $withdrawal,
        public string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'withdrawal_failed',
            'title' => 'Withdrawal failed',
            'body' => 'Your withdrawal of '.$this->withdrawal->currency.' '.number_format($this->withdrawal->amount, 2)
                .' failed. Reason: '.$this->reason,
            'withdrawal_id' => $this->withdrawal->id,
            'amount' => $this->withdrawal->amount,
            'currency' => $this->withdrawal->currency,
            'reason' => $this->reason,
        ];
    }
}
