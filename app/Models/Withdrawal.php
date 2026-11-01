<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Withdrawal extends Model
{
    protected $fillable = [
        'organizer_profile_id',
        'amount',
        'withdrawal_fee_amount',
        'net_amount',
        'currency',
        'status',
        'payment_method',
        'payment_details',
        'admin_notes',
        'failed_reason',
        'requested_at',
        'processed_at',
        'completed_at',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'withdrawal_fee_amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'payment_details' => 'array',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function organizerProfile(): BelongsTo
    {
        return $this->belongsTo(OrganizerProfile::class);
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Net amount disbursed to the organizer after the platform fee.
     */
    public function netAmount(): float
    {
        return (float) $this->net_amount;
    }

    /**
     * Platform fee retained from this withdrawal.
     */
    public function feeAmount(): float
    {
        return (float) $this->withdrawal_fee_amount;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }
}
