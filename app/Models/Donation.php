<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Donation extends Model
{
    protected $fillable = [
        'campaign_id',
        'user_id',
        'donor_name',
        'donor_email',
        'is_anonymous',
        'gross_amount',
        'platform_fee_pct',
        'platform_fee_amount',
        'payment_processing_fee',
        'net_amount',
        'currency',
        'payment_method',
        'gateway_transaction_id',
        'sender_mobile',
        'status',
        'message',
        'donated_at',
    ];

    /**
     * Financial calculation fields must not be mass-assignable from request input.
     * They are always set by DonationService after server-side fee calculation.
     */
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_anonymous' => 'boolean',
            'gross_amount' => 'decimal:2',
            'platform_fee_pct' => 'decimal:4',
            'platform_fee_amount' => 'decimal:2',
            'payment_processing_fee' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'donated_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(Transaction::class, 'transactionable');
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    /**
     * Returns the display name for the donor, respecting anonymity.
     */
    public function resolvedDonorName(): string
    {
        if ($this->is_anonymous) {
            return 'Anonymous';
        }

        return $this->donor_name ?? ($this->donor?->name ?? 'Anonymous');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
